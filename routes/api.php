<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:refresh');
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('v1')->group(function (): void {
    require __DIR__.'/api/v1.php';
});
