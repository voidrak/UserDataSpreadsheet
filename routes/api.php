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
