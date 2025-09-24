<?php

namespace App\Http\Services\Favorites;

use App\Models\Favorite;
use App\Models\FavoriteFolder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface FavoritesInterface
{
    // Favorite-related methods
    public function addToFavorites(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): Favorite;
    public function removeFromFavorites(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): bool;
    public function moveToFolder(string $favoriteId, ?int $folderId): bool;
    public function getFavorites(int $ownerId, ?string $folderName = null, bool $personal = false): Collection;
    public function isFavorite(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): bool;
    public function toggleFavorite(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): bool;
    public function getFavoriteCount(int $ownerId, ?string $folderName = null, bool $personal = false): int;
    public function getFavoritesByModel(int $ownerId, string $modelClass): Collection;
    public function clearAllFavorites(int $ownerId): bool;
    public function clearFolderFavorites(int $ownerId, string $folderName, bool $personal = false): bool;

    // Folder-related methods
    public function getFolderOrFail(string $ownerId, string $folderId): FavoriteFolder;
    public function createOrFindFolder(int $ownerId, string $name, bool $personal): FavoriteFolder;
    public function updateFolderName(int $ownerId, FavoriteFolder|string $folder, string $newName): FavoriteFolder;
    public function deleteFolder(int $ownerId, int $folderId): bool;
    public function getAllFoldersForOwner(?int $ownerId = null, string $orderBy = 'name'): Collection;
    public function getFolderForName(string $folderName, ?int $ownerId = null): FavoriteFolder|null;
}
