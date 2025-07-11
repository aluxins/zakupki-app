<?php

declare(strict_types=1);

namespace App\Http\Services\Schedules;

use App\Jobs\Purchases;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class ImportPurchases
{
    public function __invoke(): void
    {
        $this->validateConfig();
        foreach ($this->prepareClassifier() as $classifier) {
            try {
                $data = $this->prepareQueryData();
                $data['query']['classifier'] = $classifier;
                Purchases::dispatch($data, $this->typeFZ);
                $this->logSuccess($data);
            } catch (Throwable $e) {
                Log::error('Failed to initiate purchase import ' . $this->typeFZ, [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    public function __construct(
        private readonly string $typeFZ
    ) {}

    private function validateConfig(): void
    {
        foreach (['paginator', 'sort', 'days_lookback', 'stage', 'classifiers'] as $param) {
            if (!Config::has("eisgov.gosplan_info.$param")) {
                throw new RuntimeException("$param configuration is missing");
            }
        }
    }

    private function prepareQueryData(): array
    {
        $daysLookback = (int) Config::get('eisgov.gosplan_info.days_lookback');
        return [
            'query' => [
                'limit' => Config::get('eisgov.gosplan_info.paginator', 10),
                'published_after' => $this->calculatePublishedAfter($daysLookback),
                'stage' => Config::get('eisgov.gosplan_info.stage'),
                'skip' => 0,
                'sort' => Config::get('eisgov.gosplan_info.sort'),
            ]
        ];
    }

    private function prepareClassifier(): array
    {
        return preg_split("/[\s,]+/", Config::get('eisgov.gosplan_info.classifiers'));
    }

    private function calculatePublishedAfter(int $daysLookback): string
    {
        return Carbon::now()
            ->subDays($daysLookback)
            ->toIso8601ZuluString();
    }

    private function logSuccess(array $data): void
    {
        Log::info('Purchase import job dispatched ' . $this->typeFZ, [
            'query_parameters' => $data['query']
        ]);
    }
}
