<?php

namespace App\Jobs;

use App\Http\Services\OpenRouter\OpenRouterFacade;
use App\Models\AiMessage;
use App\Models\Purchase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Задание для выполнения запросов к ИИ с помощью сервиса app/Http/Services/OpenRouter.
 * Используется фасад сервиса - OpenRouterFacade.
 * Настройки сервиса определяются переменными окружения.
 */
class AIQuestionJob implements ShouldQueue
{
    use Queueable;

    /**
     *
     * @var int
     */
    public int $tries = 3;

    /**
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10*60, 30*60, 60*60];
    }

    /**
     * Create a new job instance.
     * @param string $query
     * @param string $method
     * @param Model $model
     */
    public function __construct(
        private readonly string $query,
        private readonly string $method,
        private readonly Model $model
    ) {
    }

    /**
     * Метод для обработки ответов ИИ определяется из переданного в конструктор значения переменной $method.
     * Execute the job.
     */
    public function handle(): void
    {
        $response = OpenRouterFacade::chatCompletion($this->query);
        if ($response['success']) {
            $method = $this->method;
            $this->$method($this->query, $response);
        } else {
            Log::error('AI Question Job failed', (array)$response);
        }
    }

    /**
     * Метод получает крайнюю дату поставки товаров. Сохраняет ее в соответствующую модель purchases.
     * Так же сохраняет запрос/ответ ИИ в таблицу ai_messages.
     * @param string $query
     * @param array $response
     * @return void
     */
    private function getDeliveryDate(string $query, array $response): void
    {
        /** @var Purchase $purchase */
        $purchase = $this->model;
        AiMessage::updateOrCreate(
            $purchase->id,
            $this->method . '_' . $purchase->purchase_number,
            mb_substr($query, 0, 512),
            json_encode($response)
        );

        // Ответ ИИ в приоритете берется из content. Если там даты нет, то ищем в reasoning.
        // В ответах может содержаться более одной даты. Определяется максимальная, так как, вероятно, является искомой.
        $message_content = $response['data']['choices'][0]['message']['content'] ?? '';
        $message_reasoning = $response['data']['choices'][0]['message']['reasoning'] ?? '';
        preg_match_all('/[0-9-]{4}-[0-9-]{2}-[0-9-]{2}/', $message_content, $matches_content);
        preg_match_all('/[0-9-]{4}-[0-9-]{2}-[0-9-]{2}/', $message_reasoning, $matches_reasoning);
        $result = count($matches_content[0]) ? $matches_content[0] : $matches_reasoning[0];

        if (count($result) and $delivered_at = max($result)) {
            $purchase->delivered_at = $delivered_at;
            $purchase->save();
            Log::info(
                'AI Question Job success',
                ['purchase_number' => $purchase->purchase_number, 'delivered_at' => $purchase->delivered_at]
            );
            return;
        }
        Log::error('AI Question Job failed', ['purchase_number' => $purchase->purchase_number]);
    }
}
