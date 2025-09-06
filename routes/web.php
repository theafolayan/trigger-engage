<?php

use App\Http\Controllers\TrackingController;
use App\Http\Controllers\Webhooks\PostmarkController;
use App\Http\Controllers\Webhooks\SesController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::get('/t/o/{token}', [TrackingController::class, 'open']);
Route::get('/t/c/{token}', [TrackingController::class, 'click']);
Route::get('/t/u/{token}', [TrackingController::class, 'unsubscribe']);

Route::post('/webhooks/postmark', PostmarkController::class);
Route::post('/webhooks/ses', SesController::class);

Route::get('/', function () {
    return view('welcome');
});
