<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Unified Login endpoint check.
     * Matches legacy api.php layout but injects strict rate limiting and Sanctum tokens.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = trim($request->input('email'));
        $password = trim($request->input('password'));

        // Vulnerability #4 Fix: Rate Limiting / Brute-force protection
        $throttleKey = Str::lower($email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'error' => "Too many login attempts. Please try again in {$seconds} seconds."
            ], 429);
        }

        // 1. Check Admin Users first
        $admin = Admin::where('email', $email)->first();
        if ($admin && Hash::check($password, $admin->password_hash)) {
            RateLimiter::clear($throttleKey);

            // Logged in as Admin, create standard session (for Web dashboard)
            auth()->guard('admin')->login($admin);

            return response()->json([
                'success' => true,
                'role' => 'admin',
                'redirect' => 'admin/index.php',
                'name' => $admin->name,
            ]);
        }

        // 2. Check Customer Accounts
        $customer = Customer::where('email', $email)->first();
        if ($customer && Hash::check($password, $customer->password_hash)) {
            RateLimiter::clear($throttleKey);

            // Vulnerability #3 Fix: Generate a secure Sanctum Token for the customer dashboard
            $token = $customer->createToken('evoraa-customer-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'role' => 'customer',
                'name' => $customer->name,
                'email' => $customer->email,
                'token' => $token,
                'id' => $customer->id,
            ]);
        }

        // Increment attempts on authentication failure
        RateLimiter::hit($throttleKey, 60);

        return response()->json([
            'error' => 'Invalid email address or password. Please try again.'
        ], 401);
    }

    /**
     * Admin Log Out.
     */
    public function logoutAdmin(Request $request)
    {
        auth()->guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.'
        ]);
    }
}
