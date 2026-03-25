<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Public routes (no auth required)
 * |--------------------------------------------------------------------------
 */

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

/*
 * |--------------------------------------------------------------------------
 * | Protected routes (Sanctum token required)
 * |--------------------------------------------------------------------------
 */

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);

    // Current authenticated user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

});
