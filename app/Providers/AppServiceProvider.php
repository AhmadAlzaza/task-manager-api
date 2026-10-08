<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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

        Model::shouldBeStrict(! app()->isProduction());

        RateLimiter::for('auth-login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(10)->by('login-ip:'.$request->ip()),
                Limit::perMinute(5)->by('login-email:'.$request->ip().'|'.$email),
            ];
        });

        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
