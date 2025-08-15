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
            $this->method . '_' . $purchase->purchase_number,
            mb_substr($query, 0, 512),
            json_encode($response)
        );

        if (!empty($response['data']['choices'][0]['message']['content'])) {
            $result = $response['data']['choices'][0]['message']['content'];
            preg_match('/[0-9-]{4}-[0-9-]{2}-[0-9-]{2}/', $result, $matches);
            if (isset($matches[0])) {
                $purchase->delivered_at = $matches[0];
                $purchase->save();
                Log::info(
                    'AI Question Job success',
                    ['purchase_number' => $purchase->purchase_number, 'delivered_at' => $purchase->delivered_at]
                );
                return;
            }
        }
        Log::error('AI Question Job failed', ['purchase_number' => $purchase->purchase_number]);
    }
}
