<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // You can bind services or interfaces here if needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent MySQL "Specified key was too long" error with utf8mb4
        Schema::defaultStringLength(191);
    }
}
