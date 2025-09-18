<?php

namespace App\Http\Services\Favorites;

use App\Models\FavoriteFolder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FavoriteFolderService
{

    public function getFolderOrFail(string $ownerId, string $folderId): FavoriteFolder
    {
        return FavoriteFolder::forOwner($ownerId)->findOrFail($folderId);
    }

    /**
     * Найти или создать папку с заданным именем.
     *
     * @param int $ownerId
     * @param string $name
     * @param bool $personal
     * @return FavoriteFolder
     */
    public function createOrFindFolder(int $ownerId, string $name, bool $personal): FavoriteFolder
    {
        $data['name'] = $name;
        if ($personal) {
            $data['moonshine_users_id'] = $ownerId;
        }
        return FavoriteFolder::firstOrCreate($data);
    }

    /**
     * Переименовать папку. Имя должно быть уникальным для пользователя.
     *
     * @param int $ownerId
     * @param FavoriteFolder|string $folder
     * @param string $newName
     *
     * @return FavoriteFolder
     * @throws ValidationException если имя уже занято
     */
    public function updateFolderName(int $ownerId, FavoriteFolder|string $folder, string $newName): FavoriteFolder
    {
        $folder = is_string($folder)
            ? FavoriteFolder::forOwner($ownerId)->findOrFail($folder)
            : $folder;

        $duplicate = FavoriteFolder::forOwner($ownerId)
            ->where('name', $newName)
            ->where('id', '!=', $folder->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'name' => ['Папка с таким именем уже существует.'],
            ]);
        }

        $folder->name = $newName;
        $folder->save();
        return $folder;
    }

    /**
     * Удалить папку и убрать ссылки на нее из избранного.
     *
     * @param int $ownerId
     * @param int $folderId
     * @return bool
     */
    public function deleteFolder(int $ownerId, int $folderId): bool
    {
        $result = DB::transaction(function () use ($ownerId, $folderId) {
            $folder = FavoriteFolder::forOwner($ownerId)->findOrFail($folderId);

            $folder->favorites()->delete();
            $folder->delete();
            return true;
        });

        return (bool)$result;
    }

    /**
     * Получить все папки пользователя, отсортированные по имени.
     *
     * @param int|null $ownerId
     * @param string $orderBy
     * @return Collection<FavoriteFolder>
     */
    public function getAllFoldersForOwner(?int $ownerId = null, string $orderBy = 'name'): Collection
    {
        return FavoriteFolder::forOwner($ownerId)->orderBy($orderBy)->get();
    }
}
