<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @method static whereBetween()
 */
class Purchase extends Model
{
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

    /**
     * Массово обновить или создать новые записи.
     * Вернуть идентификаторы purchase_number новых записей.
     * @param array $data
     * @return array
     */
    public static function updateOrCreate(array $data): array
    {
        foreach ($data as &$item) {
            $item['created_at'] = Carbon::now();
            $item['updated_at'] = Carbon::now();
        }

        DB::statement('LOCK TABLES purchases WRITE');
        $count_start = DB::table('purchases')->count();
        DB::table('purchases')
            ->upsert($data, uniqueBy: ['purchase_number'], update: [
                'purchase_number',
                'purchase_type',
                'classifier',
                'object_info',
                'max_price',
                'region',
                'published_at'
            ]);
        $count_insert = DB::table('purchases')->count() - $count_start;
        DB::statement('UNLOCK TABLES');
        //$last_id = (int)DB::getPdo()->lastInsertId('purchases');

        return $count_insert
            ? Arr::flatten(
                Purchase::query()
                    ->select('purchase_number')
                    ->orderBy('id', 'desc')
                    ->limit($count_insert)
                    ->get()
                    ->toArray()
            )
            : [];
    }
}
