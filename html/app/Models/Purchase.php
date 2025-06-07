<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * @property string $purchase_number
 * @property string $purchase_type
 * @property string $classifier
 * @property string $object_info
 * @property float $max_price
 * @property int $region
 * @property Carbon $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Purchase extends Model
{
    protected $table = 'purchases';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'purchase_number',
        'purchase_type',
        'classifier',
        'object_info',
        'max_price',
        'region',
        'published_at',
    ];

    protected $casts = [
        'max_price' => 'float',
        'region' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Массово обновляет или создает новые записи закупок.
     * Возвращает массив номеров закупок для новых записей.
     * Использует блокировку таблицы для точного подсчета новых записей.
     *
     * @param array<int, array<string, mixed>> $data Массив данных закупок
     * @return array<int, string> Массив номеров новых закупок
     * @throws RuntimeException
     */
    public static function updateOrCreate(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        try {
            self::addTimestamps($data);

            // Блокируем таблицу на запись для точного подсчета
            DB::statement('LOCK TABLES purchases WRITE');

            $startCount = DB::table('purchases')->count();

            DB::table('purchases')->upsert(
                $data,
                ['purchase_number'],
                [
                    'purchase_type',
                    'classifier',
                    'object_info',
                    'max_price',
                    'region',
                    'published_at',
                    'updated_at'
                ]
            );

            $insertedCount = DB::table('purchases')->count() - $startCount;

            $newPurchases = [];
            if ($insertedCount > 0) {
                $newPurchases = self::getLastInsertedPurchases($insertedCount);
            }

            // Разблокируем таблицу
            DB::statement('UNLOCK TABLES');

            return $newPurchases;

        } catch (Throwable $e) {
            // В случае ошибки обязательно разблокируем таблицу
            DB::statement('UNLOCK TABLES');

            throw new RuntimeException(
                "Failed to update or create purchases: {$e->getMessage()}",
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

    /**
     * @param int $limit
     * @return array<int, string>
     */
    private static function getLastInsertedPurchases(int $limit): array
    {
        return Arr::flatten(
            self::query()
                ->select('purchase_number')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->toArray()
        );
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'purchase_number', 'purchase_number');
    }
}
