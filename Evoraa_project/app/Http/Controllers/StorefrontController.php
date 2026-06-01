<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Banner;
use App\Models\Product;
use App\Models\Feedback;
use App\Models\ContactInquiry;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class StorefrontController extends Controller
{
    /**
     * Retrieves banners, announcements, and catalog items.
     * Matches legacy api.php `get_shop_data`.
     */
    public function getShopData()
    {
        $announcements = Announcement::orderBy('id', 'desc')->pluck('text');
        $banners = Banner::orderBy('id', 'desc')->get();
        $products = Product::orderBy('id', 'desc')->get();

        return response()->json([
            'announcements' => $announcements,
            'banners' => $banners,
            'products' => $products
        ]);
    }

    /**
     * Submits a customer contact inquiry.
     * Matches legacy api.php `submit_contact`.
     */
    public function submitContact(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:200',
            'message' => 'required|string',
        ]);

        ContactInquiry::create([
            'name' => trim($request->input('name')),
            'email' => trim($request->input('email')),
            'message' => trim($request->input('message')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Enquiry submitted successfully.'
        ]);
    }

    /**
     * Submits a customer review / testimonial.
     * Matches legacy api.php `submit_feedback`.
     */
    public function submitFeedback(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string',
        ]);

        Feedback::create([
            'customer_name' => trim($request->input('name')),
            'rating' => (int) $request->input('rating'),
            'comment' => trim($request->input('comment')),
            'approved' => true // Default to true as per legacy behavior
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Thank you for your review!'
        ]);
    }

    /**
     * Retrieves approved testimonials for storefront slider.
     * Matches legacy api.php `get_testimonials`.
     */
    public function getTestimonials()
    {
        $testimonials = Feedback::where('approved', true)
            ->orderBy('id', 'desc')
            ->limit(9)
            ->select('customer_name', 'rating', 'comment', 'created_at')
            ->get();

        return response()->json([
            'success' => true,
            'testimonials' => $testimonials
        ]);
    }

    /**
     * Registers a new customer account.
     * Matches legacy api.php `register_customer`.
     */
    public function registerCustomer(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:customers,email',
            'password' => 'required|string|min:6',
        ]);

        $customer = Customer::create([
            'name' => trim($request->input('name')),
            'email' => trim($request->input('email')),
            'password_hash' => Hash::make(trim($request->input('password'))),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'name' => $customer->name,
            'email' => $customer->email,
        ]);
    }
}
