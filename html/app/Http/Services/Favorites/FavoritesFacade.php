<?php

namespace App\Http\Services\Favorites;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Models\Favorite addToFavorites(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false)
 * @method static bool removeFromFavorites(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false)
 * @method static bool moveToFolder(string $favoriteId, ?int $folderId)
 * @method static Collection getFavorites(int $ownerId, ?string $folderName = null, bool $personal = false)
 * @method static bool isFavorite(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false)
 * @method static bool toggleFavorite(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false)
 * @method static int getFavoriteCount(int $ownerId, ?string $folderName = null, bool $personal = false)
 * @method static Collection getFavoritesByModel(int $ownerId, string $modelClass)
 * @method static bool clearAllFavorites(int $ownerId)
 * @method static bool clearFolderFavorites(int $ownerId, string $folderName, bool $personal = false)
 * @method static \App\Models\FavoriteFolder getFolderOrFail(string $ownerId, string $folderId)
 * @method static \App\Models\FavoriteFolder createOrFindFolder(int $ownerId, string $name, bool $personal)
 * @method static \App\Models\FavoriteFolder updateFolderName(int $ownerId, \App\Models\FavoriteFolder|string $folder, string $newName)
 * @method static bool deleteFolder(int $ownerId, int $folderId)
 * @method static Collection getAllFoldersForOwner(?int $ownerId = null, string $orderBy = 'name')
 * 
 * @see \App\Http\Services\Favorites\FavoritesService
 */
class FavoritesFacade extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'favorites';
    }
}
