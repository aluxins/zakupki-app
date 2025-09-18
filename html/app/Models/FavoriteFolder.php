<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $moonshine_users_id
 * @property string $name
 * @method static firstOrCreate(array $array)
 * @method static forOwner(int $ownerId)
 */
class FavoriteFolder extends Model
{

    protected $fillable = ['moonshine_users_id', 'name'];

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class)->orderBy('created_at', 'desc');
    }

    public function scopeForOwner(Builder $query, ?int $ownerId): Builder
    {
        return $query->where('moonshine_users_id', $ownerId);
    }
}
