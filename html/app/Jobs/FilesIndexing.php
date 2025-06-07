<?php

namespace App\Jobs;

use App\Exceptions\Api\ApiResponseException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FilesIndexing implements ShouldQueue
{
    use Queueable;

    /**
     *
     * @var int
     */
    public int $tries = 6;

    /**
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 10*60, 60*60, 24*60*60];
    }

    public function __construct(
        private readonly array $files
    ) {}

    /**
     * @throws Throwable
     * @throws ConnectionException
     */
    public function handle(): void
    {
        if (empty($this->files)) {
            Log::info('No files to index');
            return;
        }

        try {
            $response = $this->sendToSearchService();

            $this->logSuccess($response->json());
        } catch (ConnectionException $e) {
            $this->logError('Search service request failed', $e);
            throw new ApiResponseException('Search service request failed');
        } catch (Throwable $e) {
            $this->logError('Unexpected error during indexing', $e);
            throw $e;
        }
    }

    /**
     * @throws ConnectionException
     */
    private function sendToSearchService(): PromiseInterface|Response
    {
        $baseUrl = Config::get('services.search.url');
        $endpoint = Config::get('services.search.files_upload_endpoint');

        return Http::withHeaders([
            'accept' => 'application/json'
        ])->post($baseUrl . $endpoint, $this->files);
    }

    private function logSuccess(array $responseData): void
    {
        Log::info('Files successfully sent for indexing', [
            'indexed_count' => count($responseData),
            'purchase_number' => $this->files[0]['reg_number'] ?? 'unknown'
        ]);
    }

    private function logError(string $message, Throwable $exception): void
    {
        Log::error($message, [
            'error' => $exception->getMessage(),
            'purchase_number' => $this->files[0]['reg_number'] ?? 'unknown',
            'trace' => $exception->getTraceAsString()
        ]);
    }

    /**
     * Job failure.
     */
    public function failed(?Throwable $e): void
    {
        Log::error('jobs/callback', [
            'files' => json_encode($this->files),
            'code' => $e->getCode(),
            'message' => $e->getMessage()
        ]);
    }
}
