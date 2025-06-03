<?php

declare(strict_types=1);

namespace App\Exceptions\Api;

use RuntimeException;

class ApiResponseException extends RuntimeException
{
    private array $context;

    public function __construct(string $message, array $context = [])
    {
        parent::__construct("API error: $message");
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
