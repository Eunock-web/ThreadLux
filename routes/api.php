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

Route::get('/products', [\App\Http\Controllers\products\ProductController::class, 'index']);
Route::get('/products/{product}', [\App\Http\Controllers\products\ProductController::class, 'getProduct']);
Route::get('/categories', [\App\Http\Controllers\CategorieController::class, 'index']);

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

    // Products (Protected methods)
    Route::apiResource('products', \App\Http\Controllers\products\ProductController::class)->except(['index', 'show']);

    // Favoris
    Route::post('/favoris/{variantId}', [\App\Http\Controllers\products\FavorisController::class, 'addFavoris']);
    Route::delete('/favoris/{variantId}', [\App\Http\Controllers\products\FavorisController::class, 'removeFavoris']);
    Route::get('/favoris', [\App\Http\Controllers\products\FavorisController::class, 'getFavoris']);

    // Categories (Could be protected if you want to add more, but for now it's public)
});
