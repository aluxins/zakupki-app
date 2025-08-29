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
    const DELAY_SEND_JOB = 30; // seconds.
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
            $purchases = Purchase::query()
                ->select()
                ->join('ai_messages', 'purchases.id', '=', 'ai_messages.purchase_id', 'left');
            if($regions = Config::get('openrouter.jobs.getDeliveryDate.regions') and count($regions)) {
                $purchases->whereIn('region', $regions);
            }
            if($min_price = Config::get('openrouter.jobs.getDeliveryDate.min_price')){
                $purchases->where('max_price', '>=', $min_price);
            }
            if($max_price = Config::get('openrouter.jobs.getDeliveryDate.max_price')){
                $purchases->where('max_price', '<=', $max_price);
            }
            if($last_days = Config::get('openrouter.jobs.getDeliveryDate.last_days')){
                $purchases->where(
                    'published_at',
                    '>=',
                    Carbon::now()->subDays($last_days)->format('Y-m-d')
                );
            }
            $purchases->whereNull('delivered_at');

            $result = ['success' => [], 'failed' => []];
            $delay = 0;
            foreach ($purchases->get() as $purchase) {
                /** @var Purchase $purchase */
                if (isset($purchase->key) and str_contains((string)$purchase->key, "getDeliveryDate")) {
                    continue;
                }

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
                $files = $manticore->raw()['hits'] ?? [];
                $this->sortContract($files);
                $content = Arr::pluck($files, 'content');
                $content_str = implode(' ', $content);
                if (mb_strlen($content_str) > 100) {
                    $content_str = mb_substr($content_str, 0, 163839);
                    $this->getQuestion($query . " \n" . $content_str, $this->method, $purchase, $delay);
                    $result['success'][] = $purchase->purchase_number;
                    $delay += self::DELAY_SEND_JOB;
                } else {
                    $result['failed'][] = $purchase->purchase_number;
                }
            }
            $this->logSuccess([
                'purchases' => $purchases->count(),
                'count_success' => count($result['success']),
                'result' => $result,
            ]);
        } catch (Exception $e) {
            $this->logFailed([
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Создает задание ИИ.
     * @param string $query
     * @param string $method
     * @param Purchase $purchase
     * @param int $delay
     * @return void
     */
    private function getQuestion(string $query, string $method, Purchase $purchase, int $delay): void
    {
        AIQuestionJob::dispatch($query, $method, $purchase)->delay(now()->addSeconds($delay));
    }

    private function logSuccess(array $data): void
    {
        Log::info('AI Question success for method ' . $this->method, $data);
        Log::channel('db')->info('AI Question success for method ' . $this->method, $data);
    }

    private function logFailed(array $data): void
    {
        Log::error('AI Question failed for method ' . $this->method, $data);
        Log::channel('db')->error('AI Question failed for method ' . $this->method, $data);
    }


    /**
     * Поиск ключевых слов в строке.
     * @param string $searchString
     * @return bool
     */
    private function searchContract(string $searchString): bool
    {
        $words = explode(' ', 'проект контракт договор');
        foreach ($words as $word) {
            preg_match(
                "/" . $word . '[^.]*/ui',
                $searchString,
                $matches
            );
            if ($matches) {
                return true;
            }
        }
        return false;
    }

    /**
     * Перемещение текста договора первым элементом массива.
     * @param array $content
     * @return void
     */
    private function sortContract(array &$content): void
    {
        foreach ($content as &$hit) {
            $hit['sort'] = 0;
            if ($this->searchContract($hit['name'])) {
                $hit['sort'] = 1;
            }
        }
        usort($content, function ($item1, $item2) {
            return $item2['sort'] <=> $item1['sort'];
        });
    }
}
