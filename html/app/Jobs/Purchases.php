<?php

namespace App\Jobs;

use App\Http\Services\EisGov\HttpInterface;
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
        private readonly array $data
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(HttpInterface $http): void
    {
        try {
            $result = $http->get('purchases', $this->data);

            if (!empty($result['error'])) {
                throw new RuntimeException($result['error']);
            }

            if (empty($result['data'])) {
                Log::info('No purchases data found for query: ' . json_encode($this->data));
                return;
            }

            $insertedPurchases = $this->processPurchases($result['data'], $this->data['query']['classifier']);

            $this->logSuccess($result['data'], $insertedPurchases);
            $this->dispatchPurchaseNumberJobs($insertedPurchases);
            $this->handleNextBatch($result);

        } catch (Throwable $e) {
            Log::error('Failed to process purchases: ' . $e->getMessage(), [
                'data' => $this->data,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function processPurchases(array $data, string $classifier): array
    {
        $purchases = array_map(
            fn($purchase) => PurchasesDTO::from($purchase, $classifier)->toArray(),
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

    private function dispatchPurchaseNumberJobs(array $purchaseNumbers): void
    {
        foreach ($purchaseNumbers as $purchaseNumber) {
            PurchaseNumber::dispatch($purchaseNumber);
        }
    }

    private function handleNextBatch(array $result): void
    {
        if ($paginator = Config::get('eisgov.gosplan_info.paginator') and count($result['data']) == $paginator) {
            $data = $this->data['query']['skip'] + $paginator;
            Purchases::dispatch($data);
        }
    }
}
