<?php

namespace App\Http\Services\Schedules;

use App\Jobs\Purchases;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class ImportPurchases
{
    const classifier = ['32.30'];

    public function __invoke(): void
    {
        $data = [
            'query' => [
                'classifier' => '32.30',
                'limit' => Config::get('eisgov.gosplan_info.paginator'),
                'published_after' => Carbon::now()
                    ->subDay()->subDay()
                    ->toIso8601ZuluString(),
                'purchase_type' => 'epNotificationEF2020',
                'skip' => '0',
                'sort' => 'updated_at_asc',
            ]
        ];

        Purchases::dispatch($data);
        Log::info('Schedules test');
    }
}
