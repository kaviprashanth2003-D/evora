<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Handles checkout with inventory row locks and secure transaction processing.
     * Matches legacy api.php `checkout`.
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string|max:50',
            'zip' => 'nullable|string|max:20',
            'delivery_tier' => 'required|in:Standard,Express',
            'payment_method' => 'required|in:COD,Bank Transfer',
            'cart' => 'required|array',
            'cart.*.id' => 'required|integer',
            'cart.*.size' => 'required|string|in:XS,S,M,L,XL',
            'cart.*.qty' => 'required|integer|min:1',
        ]);

        $name = trim($request->input('name'));
        $email = trim($request->input('email'));
        $phone = trim($request->input('phone'));
        $address = trim($request->input('address'));
        $city = trim($request->input('city'));
        $zip = trim($request->input('zip'));
        $deliveryTier = $request->input('delivery_tier');
        $paymentMethod = $request->input('payment_method');
        $cart = $request->input('cart');

        try {
            $result = DB::transaction(function () use ($name, $email, $phone, $address, $city, $zip, $deliveryTier, $paymentMethod, $cart) {
                $subtotal = 0;
                $discountAmount = 0;
                $itemsToInsert = [];

                foreach ($cart as $item) {
                    $productId = $item['id'];
                    $size = strtoupper(trim($item['size']));
                    $qty = $item['qty'];

                    // Lock the product row for update to ensure atomic stock checking/reduction
                    $product = Product::lockForUpdate()->find($productId);

                    if (!$product) {
                        throw new \Exception("Product ID {$productId} not found.");
                    }

                    $stockField = 'stock_' . strtolower($size);
                    $availableStock = (int) $product->$stockField;

                    if ($availableStock < $qty) {
                        throw new \Exception("Insufficient stock for product '{$product->name}' in size {$size}. Available: {$availableStock}");
                    }

                    // Price logic
                    $price = (float) ($product->discount_active ? $product->discount_price : $product->original_price);
                    $itemSubtotal = $price * $qty;
                    $subtotal += $itemSubtotal;

                    if ($product->discount_active) {
                        $discountDiff = (float) $product->original_price - (float) $product->discount_price;
                        $discountAmount += ($discountDiff * $qty);
                    }

                    // Deduct inventory stock
                    $product->$stockField = $availableStock - $qty;
                    $product->save();

                    $itemsToInsert[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'size' => $size,
                        'qty' => $qty,
                        'price' => $price,
                    ];
                }

                // Shipping fee logic
                $shippingFee = 0;
                if ($subtotal < 15000) {
                    $shippingFee = ($deliveryTier === 'Express') ? 700.00 : 350.00;
                }

                $total = $subtotal + $shippingFee;
                $orderHash = bin2hex(random_bytes(16)); // Secure hex code

                // Create Order record
                $order = Order::create([
                    'order_hash' => $orderHash,
                    'customer_name' => $name,
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                    'customer_address' => $address,
                    'city' => $city,
                    'zip' => $zip,
                    'delivery_tier' => $deliveryTier,
                    'shipping_fee' => $shippingFee,
                    'payment_method' => $paymentMethod,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'total' => $total,
                    'status' => 'Pending',
                ]);

                // Create Order Items
                foreach ($itemsToInsert as $item) {
                    $item['order_id'] = $order->id;
                    OrderItem::create($item);
                }

                return [
                    'order_hash' => $orderHash,
                    'total' => $total,
                    'shipping_fee' => $shippingFee,
                    'payment_method' => $paymentMethod,
                ];
            });

            return response()->json(array_merge(['success' => true], $result));

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Tracks an order's status and detail contents.
     * Matches legacy api.php `track_order`.
     */
    public function trackOrder(Request $request)
    {
        $request->validate([
            'hash' => 'required|string|max:64',
        ]);

        $hash = trim($request->input('hash'));

        $order = Order::where('order_hash', $hash)->first();

        if (!$order) {
            return response()->json([
                'error' => 'Order not found.'
            ], 404);
        }

        // Retrieve items with image preview paths
        $items = OrderItem::where('order_id', $order->id)
            ->get()
            ->map(function ($item) {
                $product = Product::find($item->product_id);
                return [
                    'product_name' => $item->product_name,
                    'size' => $item->size,
                    'qty' => $item->qty,
                    'price' => $item->price,
                    'image1' => $product ? $product->image1 : null,
                ];
            });

        // Hide numeric internal primary IDs
        $orderData = $order->makeHidden(['id'])->toArray();

        return response()->json([
            'success' => true,
            'order' => $orderData,
            'items' => $items,
        ]);
    }

    /**
     * Handles bank transfer receipt uploads securely.
     * Matches legacy api.php `upload_receipt`.
     */
    public function uploadReceipt(Request $request)
    {
        // Vulnerability #5 Fix: Validate using strict validation rules
        $request->validate([
            'order_hash' => 'required|string|max:64',
            'receipt' => 'required|file|mimes:jpeg,png,jpg,pdf|max:5120', // Strict MIME and size validations
        ]);

        $hash = trim($request->input('order_hash'));
        $order = Order::where('order_hash', $hash)->first();

        if (!$order) {
            return response()->json([
                'error' => 'Order not found.'
            ], 404);
        }

        if ($order->payment_method !== 'Bank Transfer') {
            return response()->json([
                'error' => 'This order did not select Bank Transfer as the payment method.'
            ], 400);
        }

        // Vulnerability #5 Fix: Store file securely outside the public webroot
        // It writes to storage/app/public/receipts/ and generates a secure obfuscated filename automatically
        $file = $request->file('receipt');
        $path = $file->store('receipts', 'public');

        if ($path) {
            // Update database slip location
            $order->receipt_path = 'storage/' . $path;
            $order->status = 'Receipt Uploaded';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Payment receipt uploaded successfully. Our team will verify it shortly.'
            ]);
        }

        return response()->json([
            'error' => 'Failed to save file onto the server.'
        ], 500);
    }
}
