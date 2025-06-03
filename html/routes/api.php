<?php

use App\Http\Controllers\Api\PurchasesController;
use App\Http\Controllers\Api\TestController;
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
});
