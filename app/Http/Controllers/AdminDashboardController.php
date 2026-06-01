<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\ContactInquiry;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminDashboardController extends Controller
{
    /**
     * Retrieves the entire Dashboard status state.
     * Matches legacy admin/index.php data fetches.
     */
    public function index()
    {
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'Pending')->count();
        $paidOrders = Order::whereIn('status', ['Receipt Uploaded', 'Approved', 'Shipped'])->count();
        $completedRevenue = Order::whereIn('status', ['Approved', 'Shipped'])->sum('total');

        $orders = Order::orderBy('id', 'desc')->get();
        foreach ($orders as $order) {
            $order->items = $order->items()->get();
        }

        $products = Product::orderBy('id', 'desc')->get();
        $announcements = Announcement::orderBy('id', 'desc')->get();
        $banners = Banner::orderBy('id', 'desc')->get();
        $inquiries = ContactInquiry::orderBy('id', 'desc')->get();
        $adminUsers = Admin::select('id', 'name', 'email', 'created_at')->orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'paid_orders' => $paidOrders,
                'revenue' => (float) $completedRevenue,
            ],
            'orders' => $orders,
            'products' => $products,
            'announcements' => $announcements,
            'banners' => $banners,
            'inquiries' => $inquiries,
            'admins' => $adminUsers,
        ]);
    }

    /**
     * Updates an order's status.
     */
    public function updateOrderStatus(Request $request)
    {
        $request->validate([
            'order_hash' => 'required|string|exists:orders,order_hash',
            'status' => 'required|string|in:Pending,Receipt Uploaded,Approved,Shipped,Cancelled',
        ]);

        $order = Order::where('order_hash', $request->input('order_hash'))->firstOrFail();
        $order->status = $request->input('status');
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.'
        ]);
    }

    /**
     * Adds a new product drop securely with safe file uploads.
     */
    public function addProduct(Request $request)
    {
        // Vulnerability #5 Fix: Enforce strict file upload validations
        $request->validate([
            'product_code' => 'required|string|max:50|unique:products,product_code',
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:50',
            'description' => 'required|string',
            'original_price' => 'required|numeric|min:0',
            'discount_price' => 'required|numeric|min:0',
            'discount_active' => 'boolean',
            'offer_badge' => 'nullable|string|max:50',
            'stock_xs' => 'integer|min:0',
            'stock_s' => 'integer|min:0',
            'stock_m' => 'integer|min:0',
            'stock_l' => 'integer|min:0',
            'stock_xl' => 'integer|min:0',
            'image1_file' => 'required_without:image1_url|file|image|mimes:jpeg,png,webp|max:5120',
            'image2_file' => 'nullable|file|image|mimes:jpeg,png,webp|max:5120',
            'image3_file' => 'nullable|file|image|mimes:jpeg,png,webp|max:5120',
            'image4_file' => 'nullable|file|image|mimes:jpeg,png,webp|max:5120',
            'image1_url' => 'nullable|string',
            'image2_url' => 'nullable|string',
            'image3_url' => 'nullable|string',
            'image4_url' => 'nullable|string',
        ]);

        $code = trim($request->input('product_code'));
        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $code);

        // Upload and store files securely using Storage facade
        $img1 = $request->input('image1_url', '');
        $img2 = $request->input('image2_url', '');
        $img3 = $request->input('image3_url', '');
        $img4 = $request->input('image4_url', '');

        // Upload files if present
        for ($i = 1; $i <= 4; $i++) {
            $fileField = "image{$i}_file";
            if ($request->hasFile($fileField)) {
                $file = $request->file($fileField);
                $ext = $file->getClientOriginalExtension();
                $filename = "image{$i}.{$ext}";
                $path = $file->storeAs("products/{$safeCode}", $filename, 'public');
                ${"img" . $i} = 'storage/' . $path;
            }
        }

        $product = Product::create([
            'product_code' => $code,
            'name' => trim($request->input('name')),
            'category' => trim($request->input('category')),
            'description' => trim($request->input('description')),
            'image1' => $img1,
            'image2' => $img2,
            'image3' => $img3,
            'image4' => $img4,
            'original_price' => $request->input('original_price'),
            'discount_price' => $request->input('discount_price'),
            'discount_active' => $request->boolean('discount_active'),
            'offer_badge' => trim($request->input('offer_badge')),
            'stock_xs' => $request->input('stock_xs', 0),
            'stock_s' => $request->input('stock_s', 0),
            'stock_m' => $request->input('stock_m', 0),
            'stock_l' => $request->input('stock_l', 0),
            'stock_xl' => $request->input('stock_xl', 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product drop added successfully.',
            'product' => $product,
        ]);
    }

    /**
     * Edits an existing product drop safely.
     */
    public function editProduct(Request $request)
    {
        $id = $request->input('product_id');
        $product = Product::findOrFail($id);

        $request->validate([
            'product_code' => "required|string|max:50|unique:products,product_code,{$id}",
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:50',
            'description' => 'required|string',
            'original_price' => 'required|numeric|min:0',
            'discount_price' => 'required|numeric|min:0',
            'discount_active' => 'boolean',
            'offer_badge' => 'nullable|string|max:50',
            'stock_xs' => 'integer|min:0',
            'stock_s' => 'integer|min:0',
            'stock_m' => 'integer|min:0',
            'stock_l' => 'integer|min:0',
            'stock_xl' => 'integer|min:0',
            'image1_file' => 'nullable|file|image|mimes:jpeg,png,webp|max:5120',
            'image2_file' => 'nullable|file|image|mimes:jpeg,png,webp|max:5120',
            'image3_file' => 'nullable|file|image|mimes:jpeg,png,webp|max:5120',
            'image4_file' => 'nullable|file|image|mimes:jpeg,png,webp|max:5120',
            'image1_url' => 'nullable|string',
            'image2_url' => 'nullable|string',
            'image3_url' => 'nullable|string',
            'image4_url' => 'nullable|string',
        ]);

        $code = trim($request->input('product_code'));
        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $code);

        $img1 = $request->input('image1_url', '') ?: $product->image1;
        $img2 = $request->input('image2_url', '') ?: $product->image2;
        $img3 = $request->input('image3_url', '') ?: $product->image3;
        $img4 = $request->input('image4_url', '') ?: $product->image4;

        for ($i = 1; $i <= 4; $i++) {
            $fileField = "image{$i}_file";
            if ($request->hasFile($fileField)) {
                $file = $request->file($fileField);
                $ext = $file->getClientOriginalExtension();
                $filename = "image{$i}.{$ext}";
                $path = $file->storeAs("products/{$safeCode}", $filename, 'public');
                ${"img" . $i} = 'storage/' . $path;
            }
        }

        $product->update([
            'product_code' => $code,
            'name' => trim($request->input('name')),
            'category' => trim($request->input('category')),
            'description' => trim($request->input('description')),
            'image1' => $img1,
            'image2' => $img2,
            'image3' => $img3,
            'image4' => $img4,
            'original_price' => $request->input('original_price'),
            'discount_price' => $request->input('discount_price'),
            'discount_active' => $request->boolean('discount_active'),
            'offer_badge' => trim($request->input('offer_badge')),
            'stock_xs' => $request->input('stock_xs', 0),
            'stock_s' => $request->input('stock_s', 0),
            'stock_m' => $request->input('stock_m', 0),
            'stock_l' => $request->input('stock_l', 0),
            'stock_xl' => $request->input('stock_xl', 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product drop updated successfully.',
            'product' => $product,
        ]);
    }

    /**
     * Deletes an inventory product.
     */
    public function deleteProduct(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        Product::destroy($request->input('product_id'));

        return response()->json([
            'success' => true,
            'message' => 'Product drop removed successfully.'
        ]);
    }

    /**
     * Appends announcement marquee text.
     */
    public function addAnnouncement(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:255',
        ]);

        $announcement = Announcement::create([
            'text' => trim($request->input('text'))
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marquee announcement appended.',
            'announcement' => $announcement,
        ]);
    }

    /**
     * Deletes a marquee announcement.
     */
    public function deleteAnnouncement(Request $request)
    {
        $request->validate([
            'announcement_id' => 'required|integer|exists:announcements,id',
        ]);

        Announcement::destroy($request->input('announcement_id'));

        return response()->json([
            'success' => true,
            'message' => 'Marquee announcement deleted.'
        ]);
    }

    /**
     * Adds a slider banner securely.
     */
    public function addBanner(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'link_path' => 'nullable|string|max:255',
            'banner_file' => 'nullable|file|image|mimes:jpeg,png,webp|max:5120',
            'image_path_url' => 'nullable|string',
        ]);

        $imgPath = $request->input('image_path_url', '');

        if ($request->hasFile('banner_file')) {
            $file = $request->file('banner_file');
            $path = $file->store('banners', 'public');
            $imgPath = 'storage/' . $path;
        }

        if (empty($imgPath)) {
            return response()->json([
                'error' => 'Banner image is required (upload file or insert url).'
            ], 400);
        }

        $banner = Banner::create([
            'title' => trim($request->input('title')),
            'link_path' => trim($request->input('link_path', 'shop.php')),
            'image_path' => $imgPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Marketing banner slider drop successful.',
            'banner' => $banner,
        ]);
    }

    /**
     * Deletes a slider banner.
     */
    public function deleteBanner(Request $request)
    {
        $request->validate([
            'banner_id' => 'required|integer|exists:banners,id',
        ]);

        Banner::destroy($request->input('banner_id'));

        return response()->json([
            'success' => true,
            'message' => 'Marketing banner removed.'
        ]);
    }

    /**
     * Generates a new administrator account.
     */
    public function createAdmin(Request $request)
    {
        $request->validate([
            'new_admin_name' => 'required|string|max:100',
            'new_admin_email' => 'required|email|max:100|unique:admin_users,email',
            'new_admin_password' => 'required|string|min:8',
        ]);

        $admin = Admin::create([
            'name' => trim($request->input('new_admin_name')),
            'email' => trim($request->input('new_admin_email')),
            'password_hash' => Hash::make(trim($request->input('new_admin_password'))),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin account created for ' . $admin->email . '.'
        ]);
    }

    /**
     * Deletes an admin user. Can't self-delete or delete the last remaining admin.
     */
    public function deleteAdmin(Request $request)
    {
        $request->validate([
            'admin_user_id' => 'required|integer|exists:admin_users,id',
        ]);

        $targetId = (int) $request->input('admin_user_id');
        $currentAdminId = (int) auth()->guard('admin')->id();

        if ($targetId === $currentAdminId) {
            return response()->json([
                'error' => 'You cannot delete your own account.'
            ], 400);
        }

        $count = Admin::count();
        if ($count <= 1) {
            return response()->json([
                'error' => 'Cannot delete the last remaining admin account.'
            ], 400);
        }

        Admin::destroy($targetId);

        return response()->json([
            'success' => true,
            'message' => 'Admin account removed successfully.'
        ]);
    }
}
