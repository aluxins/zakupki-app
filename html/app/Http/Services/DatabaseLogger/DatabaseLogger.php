<?php

namespace App\Http\Services\DatabaseLogger;

use Monolog\Logger;

class DatabaseLogger
{
    public function __invoke(array $config): Logger
    {
        return new Logger('Database', [
            new DatabaseHandler()
        ]);
    }
}
