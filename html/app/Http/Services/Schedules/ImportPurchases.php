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
    /*private const DEFAULT_PURCHASE_TYPE_FZ44 = 'epNotificationEF2020';
    private const DEFAULT_PURCHASE_TYPE_FZ223 = 'epNotificationEF2020';*/
    private const DEFAULT_STAGE = 1;
    private const DEFAULT_SORT = 'updated_at_asc';

    public function __invoke(): void
    {
        try {
            $this->validateConfig();

            $data = $this->prepareQueryData();

            Purchases::dispatch($data, $this->typeFZ);

            $this->logSuccess($data);
        } catch (Throwable $e) {
            Log::error('Failed to initiate purchase import ' . $this->typeFZ, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function __construct(
        private readonly string $typeFZ
    ) {}

    private function validateConfig(): void
    {
        if (!Config::has('eisgov.gosplan_info.paginator')) {
            throw new RuntimeException('Paginator configuration is missing');
        }
    }

    private function prepareQueryData(): array
    {
        $daysLookback = Config::get('eisgov.gosplan_info.days_lookback', self::DEFAULT_DAYS_LOOKBACK);
        $classifier = Config::get('eisgov.gosplan_info.classifier', self::DEFAULT_CLASSIFIER);
        $stage = Config::get('eisgov.gosplan_info.stage', self::DEFAULT_STAGE);
        $sort = Config::get('eisgov.gosplan_info.sort', self::DEFAULT_SORT);
        $paginator = Config::get('eisgov.gosplan_info.paginator');
        /*$purchaseType = $this->typeFZ == 'fz44'
            ? Config::get('eisgov.gosplan_info.purchase_type_fz44', self::DEFAULT_PURCHASE_TYPE_FZ44)
            : Config::get('eisgov.imgosplan_infoport.purchase_type_fz223', self::DEFAULT_PURCHASE_TYPE_FZ223);*/


        return [
            'query' => [
                'classifier' => $classifier,
                'limit' => $paginator,
                'published_after' => $this->calculatePublishedAfter($daysLookback),
                //'purchase_type' => $purchaseType,
                'stage' => $stage,
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
        Log::info('Purchase import job dispatched ' . $this->typeFZ, [
            'query_parameters' => $data['query']
        ]);
    }
}
