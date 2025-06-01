<?php

namespace App\Http\Services\EisGov;

use Illuminate\Support\Facades\Log;

class PurchasesDTO
{
    public string $purchase_number;
    public string $purchase_type;
    public string $object_info;
    public float $max_price;
    public int $region;
    public string $published_at;
    public string $classifier;

    public function __construct($data, $classifier)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        $this->classifier = $classifier;
    }

    public function toArray(): array
    {
        return (array)$this;
    }
}
