<?php

namespace App\Jobs;

use App\Http\Services\EisGov\HttpInterface;
use \App\Http\Services\EisGov\GosplanInfo;
use \App\Http\Services\EisGov\GovRuParser;
use App\Http\Services\EisGov\PurchasesDTO;
use App\Models\Purchase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class Purchases implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly array $data,
        private readonly string $typeFZ,
        private readonly string $http
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $http = new $this->http(['typeFZ' => $this->typeFZ]);
            $result = $http->get('purchases', $this->data);

            if (!empty($result['error'])) {
                throw new RuntimeException($result['error']);
            }
            if (empty($result['data'])) {
                Log::info('No purchases data found for query: ' . json_encode($this->data));
                return;
            }

            $insertedPurchases = $this->processPurchases($result['data'], $this->data['query']['classifier'], $this->typeFZ);
            $this->logSuccess($result['data'], $insertedPurchases);
            //$this->dispatchPurchaseOrganization($result['data'], $insertedPurchases);
            $this->dispatchPurchaseNumberJobs($result['data'], $insertedPurchases);
            $this->handleNextBatch($result);

        } catch (Throwable $e) {
            Log::error('Failed to process purchases: ' . $e->getMessage(), [
                'data' => $this->data,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function processPurchases(array $data, string $classifier, string $typeFZ): array
    {
        $purchases = array_map(
            fn($purchase) => PurchasesDTO::from($purchase, $classifier, $typeFZ)->toArray(),
            $data
        );

        return Purchase::updateOrCreate($purchases);
/*        foreach ($data as &$purchase) {
            $purchase = PurchasesDTO::from($purchase, $classifier)->toArray();
        }
        return Purchase::updateOrCreate($data);*/
    }

    private function logSuccess(array $data, array $insertedPurchases): void
    {
        Log::info('Purchases processed successfully', [
            'total_from_api' => count($data),
            'total_inserted' => count($insertedPurchases),
            'query_data' => $this->data
        ]);
    }

    private function dispatchPurchaseNumberJobs(array $data, array $purchaseNumbers): void
    {
        $typeFZ = '';
        if(empty($this->typeFZ)){
            $mapped = [];
            foreach ($data as $purchase) {
                $mapped[$purchase->purchase_number] = [$purchase->type_fz, $purchase->url];
            }
        }
        foreach ($purchaseNumbers as $purchaseNumber) {
            if(empty($this->typeFZ)){
                if(empty($mapped[$purchaseNumber][0])){
                    Log::info('Not found type_fz for purchase: ' . $purchaseNumber);
                    return;
                }
                $typeFZ = $mapped[$purchaseNumber][0];
                $url = $mapped[$purchaseNumber][1];
            }
            PurchaseNumber::dispatch($purchaseNumber, $this->typeFZ ?: $typeFZ, $this->http, $url ?? null);
        }
    }

    /**
     * Создает следующее задание (пагинация).
     * @param array $result
     * @return void
     */
    private function handleNextBatch(array $result): void
    {
        if(!isset($data['query']['skip'])){
            return;
        }
        if ($paginator = Config::get('eisgov.gosplan_info.paginator') and count($result['data']) == $paginator) {
            $data = $this->data;
            $data['query']['skip'] += $paginator;
            Purchases::dispatch($data, $this->typeFZ);
        }
    }

    // Задание для определения региона заказчика закупки.
    private function dispatchPurchaseOrganization(array $data, array $purchaseNumbers): void
    {
        $mapped = [];
        foreach ($data as $purchase) {
            $mapped[$purchase->purchase_number] = [$purchase->region, $purchase->registry_url ?? null];
        }
        foreach ($purchaseNumbers as $purchaseNumber) {
            if(empty($mapped[$purchaseNumber][0])){
                PurchaseOrganization::dispatch($purchaseNumber, $mapped[$purchaseNumber][1], $this->http);
            }
        }
    }
}
