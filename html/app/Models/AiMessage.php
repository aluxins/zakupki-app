<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AiMessage extends Model
{
    public static function updateOrCreate(string $key, string $question, $response): int
    {
        $data = [
            'key' => $key,
            'question' => $question,
            'response' => $response
        ];
        self::addTimestamps($data);
        return DB::table('ai_messages')->upsert(
            $data,
            ['key'],
            ['question', 'response', 'updated_at']
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
}
