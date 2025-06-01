<?php

namespace App\Http\Services\EisGov;

class FilesDTO
{

    public string $file_name;
    public int $file_size;
    public string $description;
    public string $url;
    public string $hash;
    public string $purchase_number;

    public function __construct($data, string $purchase_number)
    {
        $this->purchase_number = $purchase_number;
        $this->file_name = $data->fileName;
        $this->file_size = $data->fileSize;
        $this->description = $data->docDescription;
        $this->url = $data->url;
        $this->hash = sha1($data->url);
    }

    public function toArray(): array
    {
        return (array)$this;
    }

}
