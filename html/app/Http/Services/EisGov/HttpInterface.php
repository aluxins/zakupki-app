<?php

namespace App\Http\Services\EisGov;

interface HttpInterface
{
    public function get(string $uri, array $options = []): array;
    public function post(string $uri, array $options = []): array;
}
