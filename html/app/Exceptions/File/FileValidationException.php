<?php

declare(strict_types=1);

namespace App\Exceptions\File;

use RuntimeException;

class FileValidationException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct("File validation error: $message");
    }
}
