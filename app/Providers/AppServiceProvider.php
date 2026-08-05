<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
        // Exemple : Limiter les tentatives de connexion / 2FA à 5 essais par minute par adresse IP ou par email
        RateLimiter::for('login', function (Request $request) {
            return Limit::perHour(5)->by($request->email ?? $request->ip())->response(function () {
                return response()->json([
                    'status'  => false,
                    'message' => 'Trop de tentatives. Patientez pendant 1 heure.'
                ], 429); // 429 = Too Many Requests
            });
        });
    }
}
