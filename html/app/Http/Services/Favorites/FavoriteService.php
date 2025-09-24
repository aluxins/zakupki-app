<?php

namespace App\Http\Services\Favorites;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use App\Models\Favorite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class FavoriteService
{

    public function __construct(
        protected FavoriteFolderService $folderService
    ) {
    }

    /**
     * Добавить объект в избранное.
     *
     * @param int $ownerId
     * @param Model $model — любая модель (Post, Comment, Profile, и т.п.)
     * @param string|null $folderName — имя папки (если нужно создать/поместить)
     * @param bool $personal - персонализация папки (false - общая, true - персональная)
     * @return Favorite
     */
    public function addToFavorites(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): Favorite
    {
        return DB::transaction(function () use ($ownerId, $model, $folderName, $personal) {
            $folderId = null;

            if ($folderName) {
                $folder = $this->folderService->createOrFindFolder($ownerId, $folderName, $personal);
                $folderId = $folder->id;
            }

            if ($this->existFromFavorites($ownerId, $model, $folderId)) {
                throw ValidationException::withMessages([
                    'error' => ['Объект уже находится в избранном.'],
                ]);
            }

            return Favorite::create([
                'moonshine_users_id' => $ownerId,
                'favoritable_id' => $model->getKey(),
                'favoritable_type' => $model::class,
                'favorite_folder_id' => $folderId,
            ]);
        });
    }

    /**
     * Удалить объект из избранного.
     *
     * @param int $ownerId
     * @param Model $model
     * @param string|null $folderName
     * @param bool $personal
     * @return bool
     */
    public function removeFromFavorites(int $ownerId, Model $model, ?string $folderName = null, bool $personal = false): bool
    {
        $folderId = null;

        if ($folderName) {
            $folder = $this->folderService->createOrFindFolder($ownerId, $folderName, $personal);
            $folderId = $folder->id;
        }

        return Favorite::forOwner($ownerId)->forModel($model)->ForFolder($folderId)->delete();
    }

    /**
     * Проверка наличия элемента в избранном.
     *
     * @param int $ownerId
     * @param Model $model
     * @param int|null $folderId
     * @return bool
     */
    public function existFromFavorites(int $ownerId, Model $model, ?int $folderId): bool
    {
        return Favorite::forOwner($ownerId)->forModel($model)->forFolder($folderId)->exists();
    }

    /**
     * Переместить избранный объект в другую папку (или убрать из папки).
     *
     * @param string $favoriteId — ID записи в таблице favorites
     * @param int|null $folderId — ID папки (или null, чтобы убрать из папки)
     *
     * @throws ModelNotFoundException если запись не найдена
     */
    public function moveToFolder(string $favoriteId, ?int $folderId): bool
    {
        $favorite = Favorite::findOrFail($favoriteId);

        if ($folderId) {
            $folder = $this->folderService->getFolderOrFail($favorite->moonshine_users_id, $folderId);
            $folderId = $folder->id;
        }

        return $favorite->update([
            'favorite_folder_id' => $folderId
        ]);
    }

    /**
     * Получить избранные элементы владельца.
     *
     * @param int $ownerId
     * @param string|null $folderName
     * @param bool $personal
     * @return Collection
     */
    public function getFavorites(int $ownerId, ?string $folderName = null, bool $personal = false): Collection
    {
        return Favorite::forOwner($ownerId)->when($folderName, function ($query, $folderName) use ($ownerId, $personal) {
            return $query->inFolder($folderName, $personal ? $ownerId : null);
        })->get();
    }
}
