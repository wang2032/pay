<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MarketController;
use App\Http\Controllers\Api\InstanceController;
use App\Http\Controllers\Api\BillController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 算力市场轻量版平台 API 路由
|
*/

// 市场相关
Route::prefix('market')->group(function () {
    Route::get('/', [MarketController::class, 'index']);
    Route::get('/filters', [MarketController::class, 'filters']);
    Route::get('/packages/{id}', [MarketController::class, 'show']);
});

// 创建实例相关
Route::prefix('instance')->group(function () {
    // 创建页初始化
    Route::get('/create/{packageId}/init', [InstanceController::class, 'createInit']);

    // 校验
    Route::post('/check-image', [InstanceController::class, 'checkImageCompatibility']);
    Route::post('/check-balance', [InstanceController::class, 'checkBalance']);

    // 创建
    Route::post('/', [InstanceController::class, 'store']);

    // 创建状态
    Route::get('/create/{taskId}/status', [InstanceController::class, 'createStatus']);
    Route::delete('/create/{taskId}', [InstanceController::class, 'cancelCreate']);

    // 实例详情
    Route::get('/{instanceId}', [InstanceController::class, 'show']);

    // 实例操作
    Route::post('/{instanceId}/stop', [InstanceController::class, 'stop']);
    Route::post('/{instanceId}/start', [InstanceController::class, 'start']);
    Route::delete('/{instanceId}', [InstanceController::class, 'destroy']);
    Route::post('/{instanceId}/renew', [InstanceController::class, 'renew']);
});

// 账单相关
Route::prefix('bill')->group(function () {
    Route::get('/balance/{userId}', [BillController::class, 'balance']);
    Route::get('/records/{userId}', [BillController::class, 'bills']);
    Route::get('/orders/{userId}', [BillController::class, 'orders']);
});