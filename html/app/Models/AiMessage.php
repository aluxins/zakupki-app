<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AiMessage extends Model
{
    public static function updateOrCreate(string $key, string $question, $response): int
    {
        return DB::table('ai_messages')->upsert(
            [
                'key' => $key,
                'question' => $question,
                'response' => $response
            ],
            ['key'],
            ['question', 'response']
        );
    }
}
