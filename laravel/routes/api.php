<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 測試路由 - 檢查前後端連接
Route::get('/test', function () {
    return response()->json([
        'message' => '前後端連接成功！',
        'status' => 'success',
        'time' => now()->format('Y-m-d H:i:s'),
        'server' => 'Laravel 11'
    ]);
});

// 暫時注釋掉anime路由，等創建控制器後再啟用
/*
use App\Http\Controllers\AnimeController;

Route::prefix('anime')->group(function () {
    Route::get('/', [AnimeController::class, 'index']);
    Route::get('/{id}', [AnimeController::class, 'show']);
    Route::post('/scrape', [AnimeController::class, 'scrape']);
    Route::get('/stats', [AnimeController::class, 'stats']);
    Route::get('/platforms/list', [AnimeController::class, 'platforms']);
});
*/
