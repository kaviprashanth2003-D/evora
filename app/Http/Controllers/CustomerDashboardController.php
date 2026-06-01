<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;

class CustomerDashboardController extends Controller
{
    /**
     * Retrieves logged-in customer's orders and wishlist product details securely.
     * Vulnerability #3 Fix: Fully protected by `auth:sanctum` middleware.
     * Relies on the authenticated user session rather than insecure client parameters.
     */
    public function index(Request $request)
    {
        // Get the authenticated customer from the Sanctum token
        $customer = $request->user();

        if (!$customer) {
            return response()->json([
                'error' => 'Unauthenticated.'
            ], 401);
        }

        // Fetch orders and include order items matching customer email
        $orders = Order::where('customer_email', $customer->email)
            ->orderBy('id', 'desc')
            ->get();

        foreach ($orders as $order) {
            $order->items = $order->items()->get();
        }

        // Fetch wishlist items from catalog
        $wishlistIds = $request->input('wishlist_ids', []);
        $wishlistProducts = [];

        if (!empty($wishlistIds) && is_array($wishlistIds)) {
            $wishlistProducts = Product::whereIn('id', $wishlistIds)
                ->select('id', 'name', 'image1', 'original_price', 'discount_price', 'discount_active', 'category')
                ->get();
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'orders' => $orders,
            'wishlist' => $wishlistProducts
        ]);
    }
}
