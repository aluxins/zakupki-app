<?php

namespace App\Jobs;

use App\Http\Services\EisGov\HttpInterface;
use App\Http\Services\EisGov\PurchasesDTO;
use App\Models\Purchase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Purchases implements ShouldQueue
{
    use Queueable;

    private array $data;

    /**
     * Create a new job instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(HttpInterface $http): void
    {
        $result = $http->get('purchases', $this->data);

        if (empty($result['error'])) {
            $insert_db = $this->updateOrCreateToDB($result['data'], $this->data['query']['classifier']);
            Log::info(
                'count-api: ' . count($result['data'])
                . '; count-insert-db: ' . count($insert_db)
                . '; data-in: ' . json_encode($this->data)
            );
            $this->createPurchaseNumber($insert_db);
            $this->createNextJob($result);
        } else {
            Log::error($result['error']);
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function updateOrCreateToDB(array $data, string $classifier): array
    {
        foreach ($data as &$purchase) {
            $purchase = (new PurchasesDTO($purchase, $classifier))->toArray();
        }
        return Purchase::updateOrCreate($data);
    }

    /**
     * Создание задания получения данных закупки (в т.ч. uri файлов).
     * @param array $purchase_numbers
     * @return void
     */
    private function createPurchaseNumber(array $purchase_numbers): void
    {
        foreach ($purchase_numbers as $purchase_number) {
            PurchaseNumber::dispatch($purchase_number);
        }
    }

    /**
     * Создание заданий для последующей выборки данных.
     * @param array $result
     * @return void
     */
    private function createNextJob(array $result): void
    {
        if ($paginator = Config::get('eisgov.gosplan_info.paginator') and count($result['data']) == $paginator) {
            $this->data['query']['skip'] += $paginator;
            Purchases::dispatch($this->data);
        }
    }
}
