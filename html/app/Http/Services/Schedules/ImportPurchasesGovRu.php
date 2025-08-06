<?php

declare(strict_types=1);

namespace App\Http\Services\Schedules;

use App\Http\Services\EisGov\GovRuParser;
use App\Jobs\Purchases;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

final class ImportPurchasesGovRu
{
    public function __invoke(): void
    {
        $this->validateConfig();
        foreach ($this->prepareClassifier(Config::get('eisgov.gov_ru_parser.param.okpd2IdsCodes')) as $key => $classifier) {
            try {
                $data = $this->prepareQueryData();
                $data['query']['okpd2IdsCodes'] = $classifier;
                $data['query']['okpd2Ids'] = $this->prepareClassifier(Config::get('eisgov.gov_ru_parser.param.okpd2Ids'))[$key];
                $data['query']['classifier'] = $data['query']['okpd2IdsCodes'];
                Purchases::dispatch($data, '', GovRuParser::class);
                $this->logSuccess($data);
            } catch (Throwable $e) {
                Log::error('Failed to initiate purchase parser', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    public function __construct(
        //private readonly string $typeFZ
    ) {}

    private function validateConfig(): void
    {
        foreach (['okpd2Ids', 'okpd2IdsCodes', 'recordsPerPage', 'pageNumber'] as $param) {
            if (!Config::has("eisgov.gov_ru_parser.param.$param")) {
                throw new RuntimeException("$param configuration is missing");
            }
        }
    }

    private function prepareQueryData(): array
    {
        $query = Config::get("eisgov.gov_ru_parser.param");
        return [
            'query' => $query
        ];
    }

    private function prepareClassifier(string $value): array
    {
        return preg_split("/[\s,]+/", $value);
    }

    private function calculatePublishedAfter(int $daysLookback): string
    {
        return Carbon::now()
            ->subDays($daysLookback)
            ->toIso8601ZuluString();
    }

    private function logSuccess(array $data): void
    {
        Log::info('Purchase parser job dispatched', [
            'query_parameters' => $data['query']
        ]);
    }
}
