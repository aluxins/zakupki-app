<?php

namespace App\Providers;

use App\Http\Services\OpenRouter\OpenRouterInterface;
use App\Http\Services\OpenRouter\OpenRouterService;
use Illuminate\Support\ServiceProvider;

class OpenRouterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(OpenRouterInterface::class, function ($app) {
            return new OpenRouterService();
        });

        $this->app->singleton('openrouter', function ($app) {
            return $app->make(OpenRouterInterface::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
