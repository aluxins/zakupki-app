<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FilesIndexing implements ShouldQueue
{
    use Queueable;

    private array $files;

    /**
     * Create a new job instance.
     */
    public function __construct(array $files)
    {
        $this->files = $files;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $response = Http::withHeaders(['accept' => 'application/json'])
                ->post('http://search.local/api/files-upload', $this->files);
            Log::info(
                'Файлы отправлены на индексацию: ' . count($response->json()) . ' шт.' .
                ' purchase_number: ' . $this->files[0]['reg_number'] ?? ''
            );
        } catch (\Exception $e) {
            Log::error('Сервис manticore-search недоступен: ' . $e->getMessage());
        }
    }
}
