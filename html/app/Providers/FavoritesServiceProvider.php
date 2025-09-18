<?php

namespace App\Providers;

use App\Http\Services\Favorites\FavoritesInterface;
use App\Http\Services\Favorites\FavoritesService;
use App\Http\Services\Favorites\FavoriteService;
use App\Http\Services\Favorites\FavoriteFolderService;
use Illuminate\Support\ServiceProvider;

class FavoritesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register individual services
        $this->app->singleton(FavoriteService::class, function ($app) {
            return new FavoriteService(
                $app->make(FavoriteFolderService::class)
            );
        });

        $this->app->singleton(FavoriteFolderService::class, function ($app) {
            return new FavoriteFolderService();
        });

        // Register unified service
        $this->app->singleton(FavoritesInterface::class, function ($app) {
            return new FavoritesService(
                $app->make(FavoriteService::class),
                $app->make(FavoriteFolderService::class)
            );
        });

        // Register facade binding
        $this->app->singleton('favorites', function ($app) {
            return $app->make(FavoritesInterface::class);
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
