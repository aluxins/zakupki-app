<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class AiMessage extends Model
{
    public static function updateOrCreate(int $purchase_id, string $key, string $question, $response): int
    {
        $data = [
            'purchase_id' => $purchase_id,
            'key' => $key,
            'question' => $question,
            'response' => $response
        ];
        self::addTimestamps($data);
        return DB::table('ai_messages')->upsert(
            $data,
            ['key'],
            ['purchase_id', 'question', 'response', 'updated_at']
        );
    }

    /**
     * Для изменения поля updated_at при обновлении записи таблицы.
     * @param array<string, mixed> $data
     */
    private static function addTimestamps(array &$data): void
    {
        $now = Carbon::now();
        $data['created_at'] = $now;
        $data['updated_at'] = $now;
    }

    public function purchase(): HasOne
    {
        return $this->hasOne(Purchase::class, 'id', 'purchase_id');
    }
}
