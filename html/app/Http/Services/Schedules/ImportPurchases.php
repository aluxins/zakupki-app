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
    private const DEFAULT_DAYS_LOOKBACK = 3;
    private const DEFAULT_CLASSIFIER = '32.30';
    private const DEFAULT_PURCHASE_TYPE = 'epNotificationEF2020';
    private const DEFAULT_SORT = 'updated_at_asc';

    public function __invoke(): void
    {
        try {
            $this->validateConfig();

            $data = $this->prepareQueryData();

            Purchases::dispatch($data);

            $this->logSuccess($data);
        } catch (Throwable $e) {
            Log::error('Failed to initiate purchase import', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function validateConfig(): void
    {
        if (!Config::has('eisgov.gosplan_info.paginator')) {
            throw new RuntimeException('Paginator configuration is missing');
        }
    }

    private function prepareQueryData(): array
    {
        $daysLookback = Config::get('eisgov.import.days_lookback', self::DEFAULT_DAYS_LOOKBACK);
        $classifier = Config::get('eisgov.import.classifier', self::DEFAULT_CLASSIFIER);
        $purchaseType = Config::get('eisgov.import.purchase_type', self::DEFAULT_PURCHASE_TYPE);
        $sort = Config::get('eisgov.import.sort', self::DEFAULT_SORT);
        $paginator = Config::get('eisgov.gosplan_info.paginator');

        return [
            'query' => [
                'classifier' => $classifier,
                'limit' => $paginator,
                'published_after' => $this->calculatePublishedAfter($daysLookback),
                'purchase_type' => $purchaseType,
                'skip' => 0,
                'sort' => $sort,
            ]
        ];
    }

    private function calculatePublishedAfter(int $daysLookback): string
    {
        return Carbon::now()
            ->subDays($daysLookback)
            ->toIso8601ZuluString();
    }

    private function logSuccess(array $data): void
    {
        Log::info('Purchase import job dispatched', [
            'query_parameters' => $data['query']
        ]);
    }
}
