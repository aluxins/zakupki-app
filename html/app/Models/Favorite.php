<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $moonshine_users_id
 * @property int $favoritable_id
 * @property string $favoritable_type
 * @property int $favorite_folder_id
 * @method static forOwner(int $ownerId)
 * @method static findOrFail(string $favoriteId)
 * @method static create(array $array)
 */
class Favorite extends Model
{

    protected $fillable = [
        'moonshine_users_id',
        'favoritable_id',
        'favoritable_type',
        'favorite_folder_id',
    ];

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(FavoriteFolder::class, 'favorite_folder_id');
    }

    public function scopeInFolder(Builder $query, string $folderName, ?int $ownerId): Builder
    {
        return $query->whereHas('folder', function (Builder $q) use ($folderName, $ownerId) {
            $q->where('name', $folderName)
                ->where('moonshine_users_id', $ownerId);
        });
    }

    public function scopeForOwner(Builder $query, int $ownerId): Builder
    {
        return $query->where('moonshine_users_id', $ownerId);
    }

    public function scopeForModel(Builder $query, Model $model): Builder
    {
        return $query->where('favoritable_type', $model::class)
            ->where('favoritable_id', $model->getKey());
    }

    public function scopeForFolder(Builder $query, ?int $folderId): Builder
    {
        return $query->where('favorite_folder_id', $folderId);
    }
}
