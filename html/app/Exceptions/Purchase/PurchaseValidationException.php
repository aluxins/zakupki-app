<?php

declare(strict_types=1);

namespace App\Exceptions\Purchase;

use RuntimeException;

class PurchaseValidationException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct("Purchase validation error: $message");
    }
}
