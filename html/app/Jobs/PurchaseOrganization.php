<?php

namespace App\Jobs;

use App\Helpers\CodesSubjects;
use \App\Http\Services\EisGov\GosplanInfo;
use \App\Http\Services\EisGov\GovRuParser;
use App\Http\Services\EisGov\HttpInterface;
use App\Models\Purchase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class PurchaseOrganization implements ShouldQueue
{
    use Queueable;

    /**
     *
     * @var int
     */
    public int $tries = 6;

    /**
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 10*60, 60*60, 24*60*60];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $purchase_number,
        private readonly string $registry_url,
        private readonly string $http,
    ) {}

    /**
     * Execute the job.
     * @throws Throwable
     */
    public function handle(): void
    {
        try {
            $http = new $this->http(['registry_url' => $this->registry_url]);
            $result = $http->get('organization/' . $this->purchase_number);

            if (!empty($result['error'])) {
                throw new RuntimeException($result['error']);
            }
            if (empty($result['data'])) {
                Log::info('No organization data found for purchase: ' . $this->purchase_number);
                return;
            }

            $code = new CodesSubjects();
            preg_match_all('([0-9]{6})', $result['data'], $matches);
            foreach ($matches[0] as $item) {
                if ($region = $code->postalCode((int)$item)) {
                    break;
                }
            }
            if (!empty($region)) {
                $purchase = Purchase::all()->where('purchase_number', '=', $this->purchase_number)->first();
                $purchase->region = $region;
                $purchase->save();
                Log::info('Process organization successfully', [$this->purchase_number, $region]);
            }

        } catch (Throwable $e) {
            Log::error('Failed to process organization: ' . $e->getMessage(), [
                'data' => [$this->purchase_number, $this->registry_url],
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
