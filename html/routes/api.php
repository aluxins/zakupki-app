<?php

use App\Http\Controllers\Api\PurchasesController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\V1\OpenRouterController;
use App\Http\Controllers\Api\V1\OpenRouterExampleController;
use App\Http\Controllers\Api\V1\FavoritesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Тестовые маршруты
Route::prefix('test')->group(function () {
    Route::get('purchases/{purchaseNumber}', [TestController::class, 'testPurchaseRetrieval']);
    Route::get('files/{hash}', [TestController::class, 'testFileSearch']);
    Route::post('purchases', [TestController::class, 'testPurchaseCreation']);
    Route::get('/files-search', [TestController::class, 'filesSearch']);
});

// Маршруты для работы с закупками
Route::prefix('purchases')->group(function () {
    Route::get('/', [PurchasesController::class, 'index']);
    Route::get('/{purchaseNumber}', [PurchasesController::class, 'show']);
    Route::get('/reIndexed/{hash}', [PurchasesController::class, 'reIndexed'])->where('hash', '[A-Za-z0-9]+')
        ->name('reIndexed');
});

// Маршруты для OpenRouter.ai API
Route::prefix('openrouter')->group(function () {
    Route::post('/chat/completions', [OpenRouterController::class, 'chatCompletion']);
    Route::get('/models', [OpenRouterController::class, 'getModels']);
    Route::get('/config', [OpenRouterController::class, 'getConfig']);
    Route::get('/test', [OpenRouterController::class, 'test']);
});

// Примеры использования OpenRouter сервиса
Route::prefix('openrouter/examples')->group(function () {
    Route::get('/direct', [OpenRouterExampleController::class, 'directUsage']);
    Route::post('/dto', [OpenRouterExampleController::class, 'dtoUsage']);
    Route::post('/custom-model', [OpenRouterExampleController::class, 'customModelUsage']);
    Route::post('/advanced', [OpenRouterExampleController::class, 'advancedUsage']);
    Route::get('/models-info', [OpenRouterExampleController::class, 'modelsInfo']);
    Route::post('/facade', [OpenRouterExampleController::class, 'facadeUsage']);
});

// Маршруты для работы с избранным
Route::prefix('favorites')->group(function () {
    Route::post('/add', [FavoritesController::class, 'addToFavorites']);
    Route::delete('/remove', [FavoritesController::class, 'removeFromFavorites']);
    Route::post('/toggle', [FavoritesController::class, 'toggleFavorite']);
    Route::get('/check', [FavoritesController::class, 'isFavorite']);
    Route::get('/list', [FavoritesController::class, 'getFavorites']);
    Route::get('/count', [FavoritesController::class, 'getFavoriteCount']);
    Route::post('/folders', [FavoritesController::class, 'createFolder']);
    Route::get('/folders', [FavoritesController::class, 'getFolders']);
    Route::delete('/folders/{folderId}', [FavoritesController::class, 'deleteFolder']);
});
