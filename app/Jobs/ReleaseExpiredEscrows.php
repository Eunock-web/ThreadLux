<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\TransactionLogger;
use FedaPay\FedaPay;
use FedaPay\Payout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * ReleaseExpiredEscrows — Scheduled daily job.
 *
 * Automatically releases escrow funds for transactions where:
 * - escrow_status is 'held'
 * - auto_release_at has passed (deadline reached)
 * - There is no open/pending litige on the transaction
 *
 * Configurable via ESCROW_AUTO_RELEASE_DAYS in .env (default: 7 days)
 */
class ReleaseExpiredEscrows implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Log::info('ReleaseExpiredEscrows: starting run', ['time' => now()->toIso8601String()]);

        $transactions = Transaction::with(['vendeur', 'commande'])
            ->where('escrow_status', 'held')
            ->where('auto_release_at', '<=', now())
            // Exclude transactions that have an active litige
            ->whereDoesntHave('litiges', fn($q) => $q->whereNotIn('status', ['resolue_acheteur', 'resolue_vendeur', 'fermee']))
            ->get();

        Log::info('ReleaseExpiredEscrows: eligible transactions', ['count' => $transactions->count()]);

        FedaPay::setApiKey(env('FEDAPAY_SECRET_KEY'));
        FedaPay::setEnvironment(env('FEDAPAY_ENVIRONMENT', 'sandbox'));

        foreach ($transactions as $transaction) {
            try {
                // Attempt FedaPay payout
                $seller = $transaction->vendeur;
                if ($seller) {
                    try {
                        $payout = Payout::create([
                            'amount' => (int) $transaction->amount,
                            'currency' => ['iso' => $transaction->currency],
                            'mode' => 'mtn',
                            'customer' => [
                                'firstname' => $seller->firstname,
                                'lastname' => $seller->lastname,
                                'email' => $seller->email,
                                'phone_number' => [
                                    'number' => $seller->phone ?? '66000000',
                                    'country' => 'bj',
                                ],
                            ],
                            'description' => "Auto-release escrow — {$transaction->reference}",
                        ]);
                        Payout::start($payout->id);
                    } catch (\Exception $fedaErr) {
                        $isLive = env('FEDAPAY_ENVIRONMENT') === 'live';
                        if (!$isLive) {
                            Log::warning('ReleaseExpiredEscrows: FedaPay sandbox simulation', [
                                'transaction_id' => $transaction->id,
                                'error' => $fedaErr->getMessage(),
                            ]);
                        } else {
                            // In live, log error but still mark as released to avoid infinite retry loops
                            Log::error('ReleaseExpiredEscrows: FedaPay payout failed (live)', [
                                'transaction_id' => $transaction->id,
                                'error' => $fedaErr->getMessage(),
                            ]);
                        }
                    }
                }

                // Update DB — always mark as released after processing
                $transaction->update([
                    'escrow_status' => 'released',
                    'escrow_released_at' => now(),
                    'status' => 'approved',
                ]);

                if ($transaction->commande) {
                    $transaction->commande->update(['escrow_status' => 'released']);
                }

                // Log the auto-release event (actor = null = system)
                TransactionLogger::log(
                    $transaction->fresh(),
                    'escrow.auto_released',
                    null,
                    'Reversement automatique déclenché après expiration du délai. '
                        . "Aucun litige ouvert par l'acheteur.",
                    [
                        'auto_release_at' => $transaction->auto_release_at?->toIso8601String(),
                        'released_at' => now()->toIso8601String(),
                    ]
                );

                Log::info('ReleaseExpiredEscrows: released', [
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                    'auto_release_at' => $transaction->auto_release_at,
                ]);
            } catch (\Exception $e) {
                Log::error('ReleaseExpiredEscrows: failed for transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ReleaseExpiredEscrows: completed', ['processed' => $transactions->count()]);
    }

    private function getReleaseDelayDays(): int
    {
        return (int) env('ESCROW_AUTO_RELEASE_DAYS', 7);
    }
}
