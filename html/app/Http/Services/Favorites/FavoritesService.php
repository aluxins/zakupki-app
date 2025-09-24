<?php

namespace App\Http\Services\Favorites;

use App\Models\Favorite;
use App\Models\FavoriteFolder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Unified service for managing favorites and favorite folders.
 * Delegates calls to appropriate specialized services.
 */
class FavoritesService
{
    public function __construct(
        protected FavoriteService $favoriteService,
        protected FavoriteFolderService $folderService
    ) {}

    // Favorite-related methods
    public function addToFavorites(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): Favorite
    {
        return $this->favoriteService->addToFavorites($ownerId, $model, $folderName, $personal);
    }

    public function removeFromFavorites(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): bool
    {
        return $this->favoriteService->removeFromFavorites($ownerId, $model, $folderName, $personal);
    }

    public function moveToFolder(string $favoriteId, ?int $folderId): bool
    {
        return $this->favoriteService->moveToFolder($favoriteId, $folderId);
    }

    public function getFavorites(int $ownerId, ?string $folderName = null, bool $personal = false): Collection
    {
        return $this->favoriteService->getFavorites($ownerId, $folderName, $personal);
    }

    // Folder-related methods
    public function getFolderOrFail(string $ownerId, string $folderId): FavoriteFolder
    {
        return $this->folderService->getFolderOrFail($ownerId, $folderId);
    }

    public function createOrFindFolder(int $ownerId, string $name, bool $personal): FavoriteFolder
    {
        return $this->folderService->createOrFindFolder($ownerId, $name, $personal);
    }

    /**
     * @throws ValidationException
     */
    public function updateFolderName(int $ownerId, FavoriteFolder|string $folder, string $newName): FavoriteFolder
    {
        return $this->folderService->updateFolderName($ownerId, $folder, $newName);
    }

    public function deleteFolder(int $ownerId, int $folderId): bool
    {
        return $this->folderService->deleteFolder($ownerId, $folderId);
    }

    public function getAllFoldersForOwner(?int $ownerId = null, string $orderBy = 'name'): Collection
    {
        return $this->folderService->getAllFoldersForOwner($ownerId, $orderBy);
    }

    public function getFolderForName(string $folderName, ?int $ownerId = null): FavoriteFolder|null
    {
        return $this->folderService->getFolderForName($folderName, $ownerId);
    }

    // Additional convenience methods
    public function isFavorite(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): bool
    {
        $folderId = null;
        if ($folderName) {
            $folder = $this->createOrFindFolder($ownerId, $folderName, $personal);
            $folderId = $folder->id;
        }

        return Favorite::forOwner($ownerId)
            ->forModel($model)
            ->forFolder($folderId)
            ->exists();
    }

    public function toggleFavorite(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): bool
    {
        if ($this->isFavorite($ownerId, $model, $folderName, $personal)) {
            $this->removeFromFavorites($ownerId, $model, $folderName, $personal);
            return false;
        } else {
            $this->addToFavorites($ownerId, $model, $folderName, $personal);
            return true;
        }
    }

    public function getFavoriteCount(int $ownerId, ?string $folderName = null, bool $personal = false): int
    {
        return $this->getFavorites($ownerId, $folderName, $personal)->count();
    }

    public function getFavoritesByModel(int $ownerId, string $modelClass): Collection
    {
        return Favorite::forOwner($ownerId)
            ->where('favoritable_type', $modelClass)
            ->get();
    }

    public function clearAllFavorites(int $ownerId): bool
    {
        return Favorite::forOwner($ownerId)->delete() > 0;
    }

    public function clearFolderFavorites(int $ownerId, string $folderName, bool $personal = false): bool
    {
        $folder = $this->createOrFindFolder($ownerId, $folderName, $personal);
        return $folder->favorites()->delete() > 0;
    }
}
