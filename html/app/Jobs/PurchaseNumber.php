<?php

declare(strict_types=1);

namespace App\Jobs;

use \App\Http\Services\EisGov\GosplanInfo;
use \App\Http\Services\EisGov\GovRuParser;
use App\Http\Services\EisGov\FilesDTO;
use App\Http\Services\EisGov\HttpInterface;
use App\Http\Services\EisGov\IndexingDTO;
use App\Models\File;
use App\Models\Purchase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use stdClass;
use Throwable;

class PurchaseNumber implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $purchase_number,
        private readonly string $typeFZ,
        private readonly string $http,
        private readonly ?string $url,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $http = new $this->http([
                'typeFZ' => $this->typeFZ,
                'url' => $this->url
            ]);
            $result = $http->get('purchases/' . $this->purchase_number);

            if (!empty($result['error'])) {
                throw new RuntimeException($result['error']);
            }

            if (empty($result['data'])) {
                Log::info('No data found for purchase number: ' . $this->purchase_number);
                return;
            }

            $insertCount = $this->processFiles($result['data']);

            PurchaseOrganization::dispatch($this->purchase_number, $result['organization'], $this->http);

            Log::info('Files processed successfully', [
                'files_updated' => $insertCount,
                'purchase_number' => $this->purchase_number
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to process purchase number: ' . $e->getMessage(), [
                'purchase_number' => $this->purchase_number,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function processFiles(mixed $files): int
    {
        if($this->http !== 'GovRuParser'){
            if ($this->typeFZ == 'fz223') {
                $files = $files->docs[0]->source->attachments->document ?? [];
            }
            if ($this->typeFZ == 'fz44') {
                $files = $files->docs[0]->source->attachmentsInfo->attachmentInfo ?? [];
            }
        }

        if (empty($files)) {
            return 0;
        }

        // Если приходит не массив, а одиночный объект - оборачиваем в массив.
        if ($files instanceof stdClass) {
            $files = [$files];
        }

        $processedFiles = array_map(
            fn($file) => FilesDTO::from(
                $file,
                $this->purchase_number,
                Purchase::all()->where('purchase_number', '=', $this->purchase_number)->first()->id
            )->toArray(),
            $files
        );
        $insertCount = File::updateOrCreate($processedFiles);

        if ($insertCount > 0) {
            $this->prepareAndDispatchIndexing($processedFiles);
        }

        return $insertCount;
    }

    private function prepareAndDispatchIndexing(array $files): void
    {
        $indexingData = array_map(function ($file) {
            $dto = IndexingDTO::from($file);
            return $dto ? $dto->toArray() : null;
        }, $files);

        $indexingData = array_filter($indexingData);

        if (!empty($indexingData)) {
            FilesIndexing::dispatch($indexingData);
        }
    }
}
