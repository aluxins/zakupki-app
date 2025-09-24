<?php

namespace App\MoonShine\Helpers;

use App\Models\Favorite;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait MorphOneFavorite
{
    public function favorite($folderId): MorphOne
    {
        return $this->morphOne(Favorite::class, 'favoritable')
            ->forOwner(auth()->id())->forFolder($folderId);
    }
}
