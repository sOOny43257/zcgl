<?php

use App\Http\Controllers\Api\AssetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 公开（无需认证）：统计概览
Route::get('/stats', [AssetController::class, 'stats']);

// 需要 API Token
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // 资产
    Route::get('/assets', [AssetController::class, 'index']);
    Route::get('/assets/search', [AssetController::class, 'search']);
    Route::get('/assets/status/{status}', [AssetController::class, 'byStatus']);
    Route::get('/assets/{code}', [AssetController::class, 'show']);

    // 借用
    Route::get('/borrows', [AssetController::class, 'borrows']);

    // 调拨单
    Route::get('/transfers', [AssetController::class, 'transfers']);
});
