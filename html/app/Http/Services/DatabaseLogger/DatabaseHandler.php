<?php

namespace App\Http\Services\DatabaseLogger;

use Monolog\Handler\AbstractProcessingHandler;
use App\Models\LogMessage;
use Monolog\LogRecord;

class DatabaseHandler extends AbstractProcessingHandler
{
    protected function write(array|LogRecord $record): void
    {
        LogMessage::create([
            'level' => $record['level'],
            'level_name' => $record['level_name'],
            'message' => $record['message'],
            'logged_at' => $record['datetime'],
            'context' => $record['context'],
            'extra' => $record['extra'],
        ]);
    }
}
