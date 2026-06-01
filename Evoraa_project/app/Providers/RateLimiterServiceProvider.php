<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class RateLimiterServiceProvider extends ServiceProvider
{
    /**
     * Bootstraps the application's request rate limiters.
     * Vulnerability #4 Fix: Protect login endpoints from password guessing.
     */
    public function boot(): void
    {
        // Rate limiter for user logins: maximum of 5 attempts per minute per IP address
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'error' => 'Too many login attempts. Please try again in 1 minute.'
                ], 429, $headers);
            });
        });

        // Rate limiter for receipt uploads and checkout: maximum of 10 requests per minute per IP
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'error' => 'Too many uploads or transactions requests. Please slow down.'
                ], 429, $headers);
            });
        });
    }
}
