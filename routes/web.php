<?php

use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['ok' => true]));

Route::get('/t/o/{token}', [TrackingController::class, 'open']);
Route::get('/t/c/{token}', [TrackingController::class, 'click']);
Route::get('/t/u/{token}', [TrackingController::class, 'unsubscribe']);

Route::get('/', function () {
    return view('welcome');
});
