<?php

use App\Http\Controllers\Api\AnimeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 測試路由
Route::get('/test', function () {
    return response()->json([
        'message' => '前後端連接成功！',
        'status' => 'success',
        'time' => now()->format('Y-m-d H:i:s'),
        'server' => 'Laravel 12'
    ]);
});

// 動漫 API 路由
Route::prefix('animes')->group(function () {
    Route::get('/', [AnimeController::class, 'index']);
    Route::get('/stats', [AnimeController::class, 'stats']);
    Route::get('/platforms', [AnimeController::class, 'platforms']);
    Route::get('/{id}', [AnimeController::class, 'show']);
});
