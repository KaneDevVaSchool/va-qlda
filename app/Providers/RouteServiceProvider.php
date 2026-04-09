<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        /** Global API throttle (Kernel api group). SPA + dev reload dễ vượt 60/phút. */
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            if (app()->environment('testing')) {
                return Limit::perMinute(1000)->by($key);
            }

            if (app()->environment('local')) {
                return Limit::none();
            }

            return Limit::perMinute(300)->by($key);
        });

        RateLimiter::for('login', function (Request $request) {
            if (app()->environment('testing')) {
                return Limit::perMinute(1000)->by('test');
            }
            $email = strtolower((string) $request->input('email', ''));

            return [
                Limit::perMinute(10)->by(sha1($email.'|'.$request->ip())),
                Limit::perMinute(60)->by('ip:'.$request->ip()),
            ];
        });

        RateLimiter::for('register', function (Request $request) {
            if (app()->environment('testing')) {
                return Limit::perMinute(1000)->by('test');
            }

            return Limit::perMinute(5)->by($request->ip());
        });

        /** OAuth Google: tách khỏi throttle:login (login dùng email+IP; request redirect/exchange không có email → dễ chạm giới hạn 10/phút). */
        RateLimiter::for('google_oauth', function (Request $request) {
            if (app()->environment('testing')) {
                return Limit::perMinute(1000)->by('test');
            }

            return Limit::perMinute(40)->by('google_oauth:'.$request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
