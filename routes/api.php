<?php

declare(strict_types=1);

use App\Http\Controllers\AuthTokenController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/token', AuthTokenController::class)->middleware('workspace');

Route::middleware(['auth:sanctum', 'workspace'])->get('/ping', function () {
    return response()->json(['data' => ['pong' => true]]);
});
