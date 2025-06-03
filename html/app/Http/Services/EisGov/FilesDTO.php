<?php

declare(strict_types=1);

namespace App\Http\Services\EisGov;

use App\Exceptions\File\FileValidationException;
use stdClass;

final readonly class FilesDTO
{
    public function __construct(
        private string $file_name,
        private int $file_size,
        private string $description,
        private string $url,
        private string $hash,
        private string $purchase_number
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->file_name)) {
            throw new FileValidationException('File name cannot be empty');
        }
        if ($this->file_size <= 0) {
            throw new FileValidationException('File size must be positive');
        }
        if (empty($this->url)) {
            throw new FileValidationException('URL cannot be empty');
        }
        if (empty($this->purchase_number)) {
            throw new FileValidationException('Purchase number cannot be empty');
        }
    }

    public static function from(stdClass $data, string $purchase_number): self
    {
        return new self(
            file_name: $data->fileName,
            file_size: (int) $data->fileSize,
            description: $data->docDescription,
            url: $data->url,
            hash: sha1($data->url),
            purchase_number: $purchase_number
        );
    }

    public function toArray(): array
    {
        return [
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'description' => $this->description,
            'url' => $this->url,
            'hash' => $this->hash,
            'purchase_number' => $this->purchase_number,
        ];
    }
}
