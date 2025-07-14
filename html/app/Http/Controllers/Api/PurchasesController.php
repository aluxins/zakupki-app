<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Services\EisGov\IndexingDTO;
use App\Jobs\FilesIndexing;
use App\Models\File;
use App\Models\Purchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurchasesController extends Controller
{
    /**
     * Получение списка закупок с возможностью фильтрации
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Purchase::query();

            // Фильтр по типу закупки
            if ($request->has('purchase_type')) {
                $query->where('purchase_type', $request->input('purchase_type'));
            }

            // Фильтр по региону
            if ($request->has('region')) {
                $query->where('region', $request->input('region'));
            }

            // Фильтр по диапазону цен
            if ($request->has('min_price')) {
                $query->where('max_price', '>=', $request->input('min_price'));
            }
            if ($request->has('max_price')) {
                $query->where('max_price', '<=', $request->input('max_price'));
            }

            // Фильтр по дате публикации
            if ($request->has('published_after')) {
                $query->where('published_at', '>=', $request->input('published_after'));
            }
            if ($request->has('published_before')) {
                $query->where('published_at', '<=', $request->input('published_before'));
            }

            // Полнотекстовый поиск по описанию
            if ($request->has('search')) {
                $query->whereFullText('object_info', $request->input('search'));
            }

            // Сортировка
            $sortField = $request->input('sort_by', 'published_at');
            $sortDirection = $request->input('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Пагинация
            $perPage = $request->input('per_page', 15);
            $purchases = $query->paginate($perPage);

            return ApiResponse::success(
                data: $purchases,
                message: 'Purchases retrieved successfully'
            );

        } catch (Throwable $e) {
            Log::error('Failed to retrieve purchases', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::error(
                message: 'Failed to retrieve purchases',
                errors: $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Получение информации о конкретной закупке
     */
    public function show(string $purchaseNumber): JsonResponse
    {
        try {
            $purchase = Purchase::query()
                ->where('purchase_number', $purchaseNumber)
                ->first();

            if (!$purchase) {
                return ApiResponse::error(
                    message: 'Purchase not found',
                    code: 404
                );
            }

            return ApiResponse::success(
                data: $purchase,
                message: 'Purchase retrieved successfully'
            );

        } catch (Throwable $e) {
            Log::error('Failed to retrieve purchase', [
                'purchase_number' => $purchaseNumber,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                message: 'Failed to retrieve purchase',
                errors: $e->getMessage(),
                code: 500
            );
        }
    }

    public function reIndexed(string $hash): JsonResponse
    {
        try {
        $file = File::all()->where('hash', $hash)->first()->toArray();
        $dto = IndexingDTO::from($file);
        FilesIndexing::dispatch([$dto->toArray()]);
        return ApiResponse::success(
            data: $file,
            message: 'Re-indexed successfully'
        );
        } catch (Throwable $e) {
            Log::error('Failed to Re-indexed file', [
                'hash' => $hash,
                'error' => $e->getMessage()
            ]);

            return ApiResponse::error(
                message: 'Failed to Re-indexed file',
                errors: $e->getMessage(),
                code: 500
            );
        }
    }
}
