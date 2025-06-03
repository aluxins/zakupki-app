<?php

declare(strict_types=1);

namespace App\Exceptions\Indexing;

use RuntimeException;

class IndexingValidationException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct("Indexing validation error: $message");
    }
} 