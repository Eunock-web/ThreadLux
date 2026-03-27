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

// Payment verification — public because guests can checkout without an account
// Security is ensured server-side by contacting FedaPay with the SECRET key
Route::post('/payment/verify', [\App\Http\Controllers\TransactionController::class, 'verify']);

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

    // Categories (Protected methods)
    Route::apiResource('categories', \App\Http\Controllers\CategorieController::class)->except(['index', 'show']);
});

/*
 * |--------------------------------------------------------------------------
 * | Webhook routes (public — NOT behind auth, but verified by signature)
 * |--------------------------------------------------------------------------
 */
Route::post('/webhook/fedapay', [\App\Http\Controllers\WebhookController::class, 'handle']);
