<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Lightweight health check — confirms the app boots and can answer.
Route::get('/health', function () {
    return response()->json([
        'app' => config('app.name'),
        'status' => 'ok',
        'time' => now()->toIso8601String(),
    ]);
})->name('health');
