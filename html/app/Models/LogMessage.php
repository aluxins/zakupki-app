<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 * @method static where(string $string, string $string1, \Illuminate\Support\Carbon $subDays)
 */
class LogMessage extends Model
{
    use MassPrunable;

    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'context' => AsArrayObject::class,
        'extra' => AsArrayObject::class,
        'logged_at' => 'immutable_datetime',
    ];

    /**
     * Get the prunable model query.
     *
     * To automatically delete logs older than a specified number of days, set the days key in the configuration and schedule log pruning:
     *```
     *  $schedule->command('model:prune', [
     *      '--model' => [
     *          App\Models\LogMessage::class,
     *      ],
     *  ])->daily();
     *```
     * @return Builder
     */
    public function prunable(): Builder
    {
        return static::where('logged_at', '<=', now()->subDays(config('logging.channels.db.days')));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'level_name',
        'level',
        'message',
        'logged_at',
        'context',
        'extra',
    ];
}
