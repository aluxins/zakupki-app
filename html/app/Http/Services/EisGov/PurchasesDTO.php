<?php

declare(strict_types=1);

namespace App\Http\Services\EisGov;

use App\Exceptions\Purchase\PurchaseValidationException;
use stdClass;

final readonly class PurchasesDTO
{
    public function __construct(
        private string $purchase_number,
        private string $purchase_type,
        private string $object_info,
        private float $max_price,
        private int $region,
        private string $type_fz,
        private string $published_at,
        private string $classifier
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->purchase_number)) {
            throw new PurchaseValidationException('Purchase number cannot be empty');
        }
//        if (empty($this->purchase_type)) {
//            throw new PurchaseValidationException('Purchase type cannot be empty');
//        }
        if ($this->max_price < 0) {
            throw new PurchaseValidationException('Max price cannot be negative');
        }
    }

    public static function from(stdClass $data, string $classifier, string $type_fz): self
    {
        return new self(
            purchase_number: (string) ($data->purchase_number ?? ''),
            purchase_type: (string) ($data->purchase_type ?? ''),
            object_info: (string) ($data->object_info ?? ''),
            max_price: (float) ($data->max_price ?? 0.0),
            region: (int) ($data->region ?? 0),
            type_fz: $type_fz,
            published_at: (string) ($data->published_at ?? ''),
            classifier: $classifier
        );
    }

    public function toArray(): array
    {
        return [
            'purchase_number' => $this->purchase_number,
            'purchase_type' => $this->purchase_type,
            'object_info' => $this->object_info,
            'max_price' => $this->max_price,
            'region' => $this->region,
            'type_fz' => $this->type_fz,
            'published_at' => $this->published_at,
            'classifier' => $this->classifier,
        ];
    }
}
