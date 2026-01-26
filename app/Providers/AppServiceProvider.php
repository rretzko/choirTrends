<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure queue rate limiter for program processing
        // Limit to 2 jobs per minute to stay under Claude API's 30K tokens/minute limit
        RateLimiter::for('program-processing', function (object $job) {
            return Limit::perMinute(2);
        });

        // Allow authenticated users to upload files via Vapor's signed S3 URLs
        Gate::define('uploadFiles', function ($user, $bucket) {
            return true;
        });
    }
}
