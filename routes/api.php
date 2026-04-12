<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LitigeController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Public routes (no auth required)
 * |--------------------------------------------------------------------------
 */

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

Route::get('/products', [\App\Http\Controllers\products\ProductController::class, 'index']);
Route::get('/products/{product}', [\App\Http\Controllers\products\ProductController::class, 'getProduct']);
Route::get('/categories', [\App\Http\Controllers\CategorieController::class, 'index']);

// Payment verification — public because guests can checkout without an account
// Security is ensured server-side by contacting FedaPay with the SECRET key
Route::post('/payment/verify', [TransactionController::class, 'verify']);

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
    Route::apiResource('products', \App\Http\Controllers\products\ProductController::class)
        ->except(['index', 'show'])
        ->middleware('role:vendeur');

    // Favoris
    Route::post('/favoris/{variantId}', [\App\Http\Controllers\products\FavorisController::class, 'addFavoris']);
    Route::delete('/favoris/{variantId}', [\App\Http\Controllers\products\FavorisController::class, 'removeFavoris']);
    Route::get('/favoris', [\App\Http\Controllers\products\FavorisController::class, 'getFavoris']);

    // Categories (Protected methods)
    Route::apiResource('categories', \App\Http\Controllers\CategorieController::class)
        ->except(['index', 'show'])
        ->middleware('role:vendeur');

    // Escrow / Payouts (Sellers only)
    Route::middleware('role:vendeur,admin')->prefix('seller/escrow')->group(function () {
        Route::get('/pending', [TransactionController::class, 'getPendingPayouts']);
        Route::post('/release/{transactionId}', [TransactionController::class, 'Payout']);
    });

    // Litige — Buyer opens a dispute
    Route::post('/litiges', [LitigeController::class, 'open']);

    // Seller views their own disputes
    Route::get('/seller/litiges', [LitigeController::class, 'sellerIndex'])->middleware('role:vendeur,admin');

    // Transaction logs — audit trail for a specific transaction
    Route::get('/transactions/{id}/logs', [TransactionController::class, 'getLogs']);

    /*
     * |--------------------------------------------------------------------------
     * | Admin routes (vendeur/admin role required)
     * |--------------------------------------------------------------------------
     */
    Route::middleware('role:vendeur,admin')->prefix('admin')->group(function () {
        Route::get('/litiges', [LitigeController::class, 'adminIndex']);
        Route::get('/litiges/{id}', [LitigeController::class, 'show']);
        Route::patch('/litiges/{id}/resolve', [LitigeController::class, 'resolve']);
    });
});

/*
 * |--------------------------------------------------------------------------
 * | Webhook routes (public — NOT behind auth, but verified by signature)
 * |--------------------------------------------------------------------------
 */
Route::post('/webhook/fedapay', [\App\Http\Controllers\WebhookController::class, 'handle']);
