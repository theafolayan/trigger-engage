<?php

declare(strict_types=1);

use App\Http\Controllers\AuthTokenController;
use App\Http\Controllers\SmtpSettingsController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', AuthTokenController::class)->middleware('workspace');

Route::middleware(['auth:sanctum', 'workspace'])->group(function (): void {
    Route::get('/ping', function () {
        return response()->json(['data' => ['pong' => true]]);
    });

    Route::get('/smtp-settings', [SmtpSettingsController::class, 'show']);
    Route::post('/smtp-settings', [SmtpSettingsController::class, 'store']);
    Route::post('/smtp-settings/test', [SmtpSettingsController::class, 'test']);
});
