<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use RomanStruk\ManticoreScoutEngine\Mysql\Builder;
use RuntimeException;
use Throwable;

/**
 * @property string $file_name
 * @property int $file_size
 * @property string $description
 * @property string $url
 * @property string $hash
 * @property string $purchase_number
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class File extends Model
{
    use Searchable;

    protected $table = 'files';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'file_name',
        'file_size',
        'description',
        'url',
        'hash',
        'purchase_number',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Массово обновляет или создает новые записи файлов.
     *
     * @param array<int, array<string, mixed>> $data Массив данных файлов
     * @return int Количество обновленных/созданных записей
     * @throws RuntimeException
     */
    public static function updateOrCreate(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        try {
            self::addTimestamps($data);

            return DB::table('files')->upsert(
                $data,
                ['hash'],
                [
                    'file_name',
                    'file_size',
                    'description',
                    'url',
                    'purchase_number',
                    'updated_at'
                ]
            );

        } catch (Throwable $e) {
            throw new RuntimeException(
                "Failed to update or create files: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    private static function addTimestamps(array &$data): void
    {
        $now = Carbon::now();
        foreach ($data as &$item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
        }
    }

    public static function filesSearch($query): Collection
    {
        $documents = File::search($query,
            fn(Builder $builder) => $builder->highlight()->select(['id', /*'content'*/])
        );
        return $documents->get();
    }
}
