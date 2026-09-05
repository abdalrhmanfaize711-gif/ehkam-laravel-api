<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
    RateLimiter::for('login', function (Request $request) {
        $identity = strtolower((string) $request->input(
            'username',
            $request->input('name', '')
        ));

        if ($request->filled('id')) {
            $identity .= '|' . (string) $request->input('id');
        }

        return Limit::perMinute(5)->by($identity . '|' . $request->ip());
    });

    RateLimiter::for('api', function (Request $request) {
        $key = $request->user()?->getAuthIdentifier();

        return Limit::perMinute((int) env('API_RATE_LIMIT', 60))
            ->by(($key ? 'user:' . $key : 'ip:' . $request->ip()));
    });
}
}
