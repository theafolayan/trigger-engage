<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\SmtpSettingsController;
use App\Http\Controllers\Api\V1\StatsController;
use App\Http\Controllers\Api\V1\TemplateController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', AuthTokenController::class)->middleware('workspace');

Route::middleware(['auth:sanctum', 'workspace'])->group(function (): void {
    Route::get('/ping', function () {
        return response()->json(['data' => ['pong' => true]]);
    });

    Route::get('/smtp-settings', [SmtpSettingsController::class, 'show']);
    Route::post('/smtp-settings', [SmtpSettingsController::class, 'store']);
    Route::post('/smtp-settings/test', [SmtpSettingsController::class, 'test']);

    Route::post('/contacts/import', [ContactController::class, 'bulkImport']);
    Route::apiResource('contacts', ContactController::class);
    Route::post('/contacts/{contact}/device-tokens', [DeviceTokenController::class, 'store']);

    Route::post('/events', [EventController::class, 'ingest']);

    Route::apiResource('templates', TemplateController::class);
    Route::post('/templates/{template}/preview', [TemplateController::class, 'preview']);
    Route::post('/templates/{template}/test', [TemplateController::class, 'test']);

    Route::middleware('admin')->get('/admin/stats', StatsController::class);
});
