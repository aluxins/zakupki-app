<?php

declare(strict_types=1);

namespace App\Http\Services\EisGov;

use App\Exceptions\Indexing\IndexingValidationException;
use App\Models\File;

final readonly class IndexingDTO
{
    public function __construct(
        private int $id,
        private string $url,
        private string $reg_number
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if ($this->id <= 0) {
            throw new IndexingValidationException('ID must be a positive number');
        }
        if (empty($this->url)) {
            throw new IndexingValidationException('URL cannot be empty');
        }
        if (empty($this->reg_number)) {
            throw new IndexingValidationException('Registration number cannot be empty');
        }
    }

    public static function from(array $fileData): ?self
    {
        $file = File::query()
            ->where('hash', '=', sha1($fileData['url']))
            ->select('id')
            ->first();

        if (!$file) {
            return null;
        }

        return new self(
            id: $file->id,
            url: $fileData['url'],
            reg_number: $fileData['purchase_number']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'reg_number' => $this->reg_number,
        ];
    }
}
