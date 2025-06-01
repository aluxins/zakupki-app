<?php

namespace App\Providers;

use App\Http\Services\EisGov\GosplanInfo;
use App\Http\Services\EisGov\HttpInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HttpInterface::class, GosplanInfo::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
