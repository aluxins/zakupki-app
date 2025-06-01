<?php

namespace App\Http\Services\EisGov;

use App\Models\File;

class IndexingDTO
{

    public mixed $id;
    public mixed $url;
    public mixed $reg_number;

    public function __construct($data)
    {
        $file = File::query()
            ->where('hash', '=', sha1($data['url']))
            ->select('id')
            ->first();
        if($file){
            $this->id = $file->id;
            $this->url = $data['url'];
            $this->reg_number = $data['purchase_number'];
        }

    }

    public function toArray(): array
    {
        return (array)$this;
    }
}
