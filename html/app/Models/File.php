<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class File extends Model
{
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

    /**
     * Массово обновить или создать новые записи.
     * @param array $data
     * @return int
     */
    public static function updateOrCreate(array $data): int
    {
        foreach ($data as &$item) {
            $item['created_at'] = Carbon::now();
            $item['updated_at'] = Carbon::now();
        }
        return DB::table('files')->
        upsert($data, uniqueBy: ['hash'], update: [
            'file_name',
            'file_size',
            'description',
            'url',
            'hash',
            'purchase_number',
        ]);
    }
}
