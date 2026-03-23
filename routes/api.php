<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Public routes (no auth required)
 * |--------------------------------------------------------------------------
 */

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// FedaPay webhook — must NOT be behind auth (called by FedaPay servers)
Route::post('/fedapay/webhook', [WebhookController::class, 'webhook']);

// Callback after a hosted-page payment redirect
Route::get('/callback/{transactionId}', [TransactionController::class, 'callBack']);

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

    // Payments
    Route::post('/transaction/{produitId}', [TransactionController::class, 'makeTransactionWithoutRedirection']);
});
