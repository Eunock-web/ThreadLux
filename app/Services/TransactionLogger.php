<?php

namespace App\Services;

use App\Models\Log;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Request;

/**
 * TransactionLogger — Centralised audit trail for all escrow/transaction state changes.
 *
 * Logs every status change with: timestamp, event type, author (user_id), description, payload.
 */
class TransactionLogger
{
    /**
     * Record a status change event for a transaction.
     *
     * @param  Transaction  $transaction  The transaction being tracked.
     * @param  string       $event        Event identifier, e.g. "transaction.created", "escrow.released".
     * @param  User|null    $actor        The user who triggered this change (null = system/job).
     * @param  string       $description  Human-readable description of what happened.
     * @param  array        $payload      Optional additional data to store (amounts, statuses, etc.).
     */
    public static function log(
        Transaction $transaction,
        string $event,
        ?User $actor,
        string $description,
        array $payload = []
    ): Log {
        return Log::create([
            'transaction_id' => $transaction->id,
            'commande_id' => $transaction->commande_id,
            'user_id' => $actor?->id,
            'event_type' => $event,
            'status' => $transaction->escrow_status ?? $transaction->status,
            'description' => $description,
            'payload' => array_merge([
                'escrow_status' => $transaction->escrow_status,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
            ], $payload),
            'metadata' => [
                'actor' => $actor ? "{$actor->firstname} {$actor->lastname} ({$actor->email})" : 'system',
                'actor_id' => $actor?->id,
                'role' => $actor?->role ?? 'system',
            ],
            'ip_address' => Request::ip(),
            'created_at' => now(),
        ]);
    }
}
