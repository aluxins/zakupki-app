<?php

namespace App\Http\Services\Schedules;

use App\Jobs\AIQuestionJob;
use App\Models\File;
use App\Models\Purchase;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RomanStruk\ManticoreScoutEngine\Mysql\Builder;
use RuntimeException;

/**
 * Менеджер заданий для запросов к ИИ.
 */
final readonly class AIQuestion
{
    public function __invoke(): void
    {
        $this->validateConfig($this->method);
        $method = $this->method;
        $this->$method();
    }

    public function __construct(
        private string $method
    ) {
    }

    private function validateConfig(string $method): void
    {
        if (!method_exists($this, $method)) {
            throw new RuntimeException("Method $method does not exist.");
        }
    }

    /**
     * Отправляет задания для определения крайней даты поставки товаров.
     * @return void
     */
    private function getDeliveryDate(): void
    {
        try {
            $purchases = Purchase::all()
                ->whereIn('region', Config::get('openrouter.jobs.getDeliveryDate.regions'))
                ->where('max_price', '>=', Config::get('openrouter.jobs.getDeliveryDate.min_price'))
                ->where(
                    'published_at',
                    '>=',
                    Carbon::now()->subDays(Config::get('openrouter.jobs.getDeliveryDate.last_days'))->format('Y-m-d')
                )
                ->whereNull('delivered_at');

            $result = ['success' => [], 'failed' => []];
            foreach ($purchases as $purchase) {
                $addDay = match ($purchase->placing_way) {
                    'аукцион' => 10,
                    'котировок' => 3,
                    default => 5
                };
                $date = Carbon::parse($purchase->finished_at)->addDays($addDay)->format('Y-m-d');
                $query = "Определи в следующем тексте срок поставки товара (то есть крайнюю дату поставки)?" .
                    " Дата заключения контракта $date." .
                    " Результат представь в формате даты YYYY-MM-DD без рассуждений.";

                $manticore = File::search('', fn(Builder $builder) => $builder
                    ->select(['name', 'content']))
                    ->where('reg_number', $purchase->purchase_number);
                $content = Arr::pluck($manticore->raw()['hits'] ?? [], 'content');
                $content_str = implode(' ', $content);
                if (mb_strlen($content_str) > 100) {
                    $content_str = mb_substr($content_str, 0, 163839);
                    $this->getQuestion($query . " \n" . $content_str, $this->method, $purchase);
                    $result['success'][] = $purchase->purchase_number;
                } else {
                    $result['failed'][] = $purchase->purchase_number;
                }
            }
            $this->logSuccess([
                'purchases' => $purchases->count(),
                'result' => $result,
            ]);
        } catch (Exception $e) {
            $this->logFailed([
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getQuestion(string $query, string $method, Purchase $purchase): void
    {
        AIQuestionJob::dispatch($query, $method, $purchase);
    }

    private function logSuccess(array $data): void
    {
        Log::info('AI Question success for method ' . $this->method, $data);
    }

    private function logFailed(array $data): void
    {
        Log::error('AI Question failed for method ' . $this->method, $data);
    }
}
