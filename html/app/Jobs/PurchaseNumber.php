<?php

namespace App\Jobs;

use App\Http\Services\EisGov\FilesDTO;
use App\Http\Services\EisGov\HttpInterface;
use App\Http\Services\EisGov\IndexingDTO;
use App\Models\File;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use stdClass;

class PurchaseNumber implements ShouldQueue
{
    use Queueable;

    private string $purchase_number;

    /**
     * Create a new job instance.
     */
    public function __construct(string $purchase_number)
    {
        $this->purchase_number = $purchase_number;
    }

    /**
     * Execute the job.
     */
    public function handle(HttpInterface $http): void
    {
        $result = $http->get('purchases/' . $this->purchase_number);
        if (empty($result['error'])) {
            $insert_count_db = $this->updateOrCreateToDB($result['data'], $this->purchase_number);
            Log::info('Добавлено/обновлено файлов: ' . $insert_count_db . '; purchase_number: ' . $this->purchase_number);
        } else {
            Log::error($result['error']);
        }
    }

    /**
     * @param stdClass $data
     * @param string $purchase_number
     * @return int
     */
    private function updateOrCreateToDB(stdClass $data, string $purchase_number): int
    {
        $files = $data->docs[0]->source->attachmentsInfo->attachmentInfo ?? [];
        foreach ($files as &$file) {
            $file = (new FilesDTO($file, $purchase_number))->toArray();
        }
        if($count = File::updateOrCreate($files)){
            $this->sendFilesForIndexing($files);
        }
        return $count;
    }

    private function sendFilesForIndexing($files): void
    {
        foreach ($files as &$file) {
            $file = (new IndexingDTO($file))->toArray();
        }
        FilesIndexing::dispatch($files);
    }
}
