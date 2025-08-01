<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

// Simple test route
Route::post('/test-users', function () {
    return response()->json(['message' => 'Route works!']);
});

// Your actual route
Route::post('/post-users', [UserController::class, 'store']);

// Test sheets route
Route::get('/test-sheets', function () {
    try {
        $service = new \App\Services\GoogleSheetsService();
        $sheets = $service->getSheetNames();
        return response()->json(['sheets' => $sheets]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});
