<?php

namespace App\Providers;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
 
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
RateLimiter::for('api', function (Request $request) {
    if ($request->user()) {
        return Limit::perMinute(120)->by($request->user()->id);
    }
    return Limit::perMinute(30)->by($request->ip());
});
}
}
