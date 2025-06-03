<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Services\EisGov\HttpInterface;
use App\Models\File;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use RuntimeException;
use Throwable;

class TestController extends Controller
{
    public function __construct(
        private readonly HttpInterface $httpClient
    ) {}

    /**
     * Тестовый эндпоинт для проверки получения данных о закупке
     */
    public function testPurchaseRetrieval(string $purchaseNumber): JsonResponse
    {
        try {
            $result = $this->httpClient->get('purchases/' . $purchaseNumber);

            if (!empty($result['error'])) {
                throw new RuntimeException($result['error']);
            }

            if (empty($result['data'])) {
                return ApiResponse::error(
                    message: 'No data found for purchase number: ' . $purchaseNumber,
                    code: 404
                );
            }

            return ApiResponse::success(
                data: $result['data'],
                message: 'Purchase data retrieved successfully'
            );

        } catch (Throwable $e) {
            Log::error('Failed to retrieve purchase data', [
                'purchase_number' => $purchaseNumber,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                message: 'Failed to retrieve purchase data',
                errors: $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Тестовый эндпоинт для проверки поиска файла по хешу
     */
    public function testFileSearch(string $hash): JsonResponse
    {
        try {
            $file = File::query()
                ->where('hash', '=', $hash)
                ->first();

            if (!$file) {
                return ApiResponse::error(
                    message: 'File not found with hash: ' . $hash,
                    code: 404
                );
            }

            return ApiResponse::success(
                data: $file,
                message: 'File found successfully'
            );

        } catch (Throwable $e) {
            Log::error('Failed to search file', [
                'hash' => $hash,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                message: 'Failed to search file',
                errors: $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Тестовый эндпоинт для проверки создания закупок
     */
    public function testPurchaseCreation(): JsonResponse
    {
        try {
            $testData = $this->generateTestPurchaseData();

            $result = Purchase::updateOrCreate($testData);

            return ApiResponse::success(
                data: ['created_purchase_numbers' => $result],
                message: 'Test purchases created successfully'
            );

        } catch (Throwable $e) {
            Log::error('Failed to create test purchases', [
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                message: 'Failed to create test purchases',
                errors: $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Генерирует тестовые данные для закупок
     *
     * @return array<int, array<string, mixed>>
     */
    private function generateTestPurchaseData(): array
    {
        return [
            [
                'purchase_number' => '1018751008325001352',
                'purchase_type' => 'epNotificationEF2020',
                'object_info' => 'Поставка спортивного инвентаря',
                'max_price' => '43560.66',
                'region' => '38',
                'published_at' => Carbon::now()->format('Y-m-d H:i:s.u'),
            ],
            [
                'purchase_number' => '1041761041326000019',
                'purchase_type' => 'epNotificationEF2020',
                'object_info' => 'Поставка и монтаж уличной трибуны для зрителей',
                'max_price' => '10000.00',
                'region' => '4',
                'published_at' => Carbon::now()->format('Y-m-d H:i:s.u'),
            ],
        ];
    }

    public function filesSearch(Request $request): JsonResponse
    {
        $search = $request::get('search');
        $baseUrl = Config::get('services.search.url');
        $endpoint = '/api/files/documents';
        try {
            $response = Http::withHeaders([
                'accept' => 'application/json'
            ])->get($baseUrl . $endpoint, ['search' => $search]);
            return ApiResponse::success(
                data: $response->json(),
                message: 'Purchase retrieved successfully'
            );
        } catch (ConnectionException $e) {
            return ApiResponse::error(
                message: 'Failed to retrieve purchase',
                errors: $e->getMessage(),
                code: 500
            );
        }
    }
}
