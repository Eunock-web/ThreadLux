<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransactionLogger;
use FedaPay\FedaPay;
use FedaPay\Transaction as FedaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function verify(Request $request)
    {
        // 1. Configure the SDK with the SECRET key (never the public key on the backend)
        FedaPay::setApiKey(env('FEDAPAY_SECRET_KEY'));
        FedaPay::setEnvironment(env('FEDAPAY_ENVIRONMENT', 'sandbox'));

        try {
            $transactionId = $request->input('transaction_id');
            $customerEmail = $request->input('customer_email');
            $amount = $request->input('amount');

            // Security: Vendors and Admins are NOT allowed to make purchases.
            // This ensures clean escrow separation.
            $user = auth('sanctum')->user();
            if ($user && in_array($user->role, ['vendeur', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les comptes vendeurs/admins ne sont pas autorisés à effectuer des achats.',
                ], 403);
            }

            if (!$transactionId) {
                return response()->json(['success' => false, 'message' => 'transaction_id requis'], 422);
            }

            // 2. Retrieve the REAL status from FedaPay (security - never trust the client)
            $fedaTransaction = FedaTransaction::retrieve($transactionId);

            // 3. Find Seller and Calculate Escrow
            $cartItems = $request->input('cart', []);
            $vendeurId = null;
            if (!empty($cartItems)) {
                $firstProduct = \App\Models\Product::find($cartItems[0]['id']);
                $vendeurId = $firstProduct ? $firstProduct->user_id : null;
            }

            Log::info('FedaPay verify response', [
                'transaction_id' => $transactionId,
                'status' => $fedaTransaction->status,
                'amount' => $fedaTransaction->amount,
                'customer_email' => $customerEmail,
                'vendeur_id' => $vendeurId,
            ]);

            // Auto-release delay from config (default: 1 minute for testing, or use days)
            $autoReleaseMinutes = (int) env('ESCROW_AUTO_RELEASE_MINUTES', 0);
            $autoReleaseDays = (int) env('ESCROW_AUTO_RELEASE_DAYS', 7);

            // 4. Persist the transaction (best-effort — won't block checkout on DB error)
            $transaction = null;
            try {
                $transaction = Transaction::updateOrCreate(
                    ['provider_ref' => (string) $transactionId],
                    [
                        'reference' => 'TLX-' . strtoupper(substr(md5($transactionId), 0, 8)),
                        'acheteur_id' => auth('sanctum')->id() ?? null,
                        'vendeur_id' => $vendeurId,
                        'amount' => $fedaTransaction->amount ?? $amount,
                        'currency' => $fedaTransaction->currency->iso ?? 'XOF',
                        'payment_method' => 'mobile_money',
                        'provider' => 'fedapay',
                        'status' => $fedaTransaction->status,
                        'escrow_status' => ($fedaTransaction->status === 'approved') ? 'held' : 'none',
                        'escrow_held_at' => ($fedaTransaction->status === 'approved') ? now() : null,
                        'auto_release_at' => ($fedaTransaction->status === 'approved')
                            ? ($autoReleaseMinutes > 0 ? now()->addMinutes($autoReleaseMinutes) : now()->addDays($autoReleaseDays))
                            : null,
                        'description' => "Commande client: {$customerEmail}",
                    ]
                );

                // Log the transaction creation event
                if ($transaction && $fedaTransaction->status === 'approved') {
                    TransactionLogger::log(
                        $transaction,
                        'transaction.created',
                        auth('sanctum')->user(),
                        "Transaction créée et fonds mis en escrow. FedaPay ref: {$transactionId}. Auto-release prévu dans {$autoReleaseDays} jours.",
                        ['provider_ref' => $transactionId, 'fedapay_status' => $fedaTransaction->status]
                    );
                }

                Log::info('Transaction saved to DB', ['provider_ref' => $transactionId, 'transaction_db_id' => $transaction->id]);
            } catch (\Exception $dbErr) {
                Log::warning('Transaction DB save failed (non-critical)', [
                    'provider_ref' => $transactionId,
                    'error' => $dbErr->getMessage(),
                ]);
            }

            if ($fedaTransaction->status === 'approved') {
                // 5. Create Commande Record
                try {
                    $commande = \App\Models\Commande::create([
                        'reference' => 'ORD-' . strtoupper(substr(uniqid(), -8)),
                        'acheteur_id' => auth('sanctum')->id() ?? null,
                        'vendeur_id' => $vendeurId,
                        'montant_total' => $fedaTransaction->amount ?? $amount,
                        'status' => 'paid',
                        'escrow_status' => 'held',
                    ]);

                    if ($transaction) {
                        $transaction->update(['commande_id' => $commande->id]);
                    }

                    // Create items
                    foreach ($cartItems as $item) {
                        $p = \App\Models\Product::find($item['id']);
                        \App\Models\CommandeItem::create([
                            'commande_id' => $commande->id,
                            'produit_id' => $item['id'],
                            'nom_produit' => $p ? $p->name : 'Produit inconnu',
                            'qte' => $item['quantity'],
                            'prix_unitaire' => $p ? $p->prix : 0,
                            'prix_total' => $p ? ($p->prix * $item['quantity']) : 0,
                        ]);
                    }
                    Log::info('Commande created successfully', ['commande_id' => $commande->id]);
                } catch (\Exception $cmdErr) {
                    Log::error('Commande creation failed', ['error' => $cmdErr->getMessage()]);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Paiement validé avec succès.',
                    'transaction_id' => $transaction ? $transaction->id : null,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Le paiement est en statut : ' . $fedaTransaction->status,
                'status' => $fedaTransaction->status,
            ], 400);
        } catch (\Exception $e) {
            Log::error('FedaPay verify error', [
                'message' => $e->getMessage(),
                'transaction_id' => $request->input('transaction_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getPendingPayouts()
    {
        $payouts = Transaction::where('escrow_status', 'held')
            ->with(['commande.items.produit', 'acheteur'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payouts
        ]);
    }

    public function Payout(Request $request, $transactionId)
    {
        $user = auth()->user();

        // 1. Find the transaction
        $transaction = Transaction::where('id', $transactionId)
            ->firstOrFail();

        if ($transaction->escrow_status !== 'held') {
            return response()->json([
                'success' => false,
                'message' => $transaction->escrow_status === 'en_litige'
                    ? 'Cette transaction est en litige. Veuillez attendre la résolution avant de libérer les fonds.'
                    : "Cette transaction n'est pas en attente de déblocage.",
            ], 400);
        }

        // 2. Configure FedaPay
        FedaPay::setApiKey(env('FEDAPAY_SECRET_KEY'));
        FedaPay::setEnvironment(env('FEDAPAY_ENVIRONMENT', 'sandbox'));

        try {
            // 3. Trigger FedaPay Payout (Real transfer)
            $vendor = $transaction->vendeur;

            if (!$vendor) {
                Log::error('Payout failed: no vendor associated with transaction', ['transaction_id' => $transactionId]);
                return response()->json([
                    'success' => false,
                    'message' => "Impossible de traiter le virement : aucun vendeur n'est associé à cette transaction."
                ], 422);
            }

            $payoutPhone = $request->input('phone_number') ?? $vendor->phone;

            if (!$payoutPhone) {
                return response()->json([
                    'success' => false,
                    'message' => "Le numéro de téléphone pour le reversement n'est pas configuré.",
                ], 422);
            }

            // Basic Benin Network Detection (10-digit support for 01 plan)
            $cleanNumber = preg_replace('/[^0-9]/', '', $payoutPhone);
            if (strlen($cleanNumber) === 10 && str_starts_with($cleanNumber, '01')) {
                $checkNumber = substr($cleanNumber, 2);
            } else {
                $checkNumber = $cleanNumber;
            }

            $prefix = substr($checkNumber, 0, 2);
            $moovPrefixes = ['60', '63', '64', '65', '94', '95'];
            $payoutMode = in_array($prefix, $moovPrefixes) ? 'moov' : 'mtn';

            try {
                Log::info('Initiating FedaPay Payout', [
                    'transaction_id' => $transactionId,
                    'amount' => $transaction->amount,
                    'mode' => $payoutMode,
                    'phone' => $cleanNumber
                ]);

                $payout = \FedaPay\Payout::create([
                    'amount' => (int) $transaction->amount,
                    'currency' => ['iso' => $transaction->currency ?? 'XOF'],
                    'mode' => $payoutMode,
                    'customer' => [
                        'firstname' => $vendor->firstname ?? 'Vendeur',
                        'lastname' => $vendor->lastname ?? 'ThreadLux',
                        'email' => $vendor->email ?? 'support@threadlux.com',
                        'phone_number' => [
                            'number' => $cleanNumber,
                            'country' => 'bj'
                        ]
                    ],
                    'description' => "Payout pour la transaction {$transaction->reference}"
                ]);

                Log::info('FedaPay Payout created, starting...', ['payout_id' => $payout->id]);

                \FedaPay\Payout::start($payout->id);

                Log::info('FedaPay Payout started successfully', [
                    'transaction_id' => $transactionId,
                    'payout_id' => $payout->id,
                    'vendor_id' => $vendor->id
                ]);
            } catch (\Exception $fedaErr) {
                Log::error('FedaPay Payout API error', [
                    'error' => $fedaErr->getMessage(),
                    'transaction_id' => $transactionId,
                    'trace' => $fedaErr->getTraceAsString()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur API FedaPay : ' . $fedaErr->getMessage()
                ], 502);  // Use 502 for external provider issues
            }

            // 4. Update Database
            $transaction->update([
                'escrow_status' => 'released',
                'escrow_released_at' => now(),
                'status' => 'approved'
            ]);

            if ($transaction->commande) {
                $transaction->commande->update(['escrow_status' => 'released']);
            }

            // 5. Log the manual payout event
            TransactionLogger::log(
                $transaction->fresh(),
                'escrow.released',
                $user,
                'Fonds débloqués manuellement pour le vendeur ' . ($vendor->firstname ?? 'Inconnu'),
                ['released_by' => $user->role, 'actor_id' => $user->id, 'vendor_id' => $vendor->id]
            );

            // 6. Send Email Notifications
            try {
                if ($vendor->email) {
                    \Illuminate\Support\Facades\Mail::to($vendor->email)
                        ->queue(new \App\Mail\PayoutReleasedSeller($transaction));
                }

                if ($transaction->acheteur && $transaction->acheteur->email) {
                    \Illuminate\Support\Facades\Mail::to($transaction->acheteur->email)
                        ->queue(new \App\Mail\PayoutReleasedBuyer($transaction));
                }

                Log::info('Payout notification emails queued');
            } catch (\Exception $mailErr) {
                Log::error('Payout notification emails failed to queue', ['error' => $mailErr->getMessage()]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fonds débloqués avec succès.'
            ]);
        } catch (\Exception $e) {
            Log::error('FedaPay Payout failed', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du virement FedaPay : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/transactions/{id}/logs
     * Returns the full audit trail (status change history) for a transaction.
     */
    public function getLogs(int $id)
    {
        $user = auth()->user();

        $transaction = Transaction::findOrFail($id);

        // Only the buyer, seller, or admin of this transaction can view logs
        if (
            $transaction->acheteur_id !== $user->id &&
            $transaction->vendeur_id !== $user->id &&
            !in_array($user->role, ['admin', 'vendeur'])
        ) {
            return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);
        }

        $logs = $transaction
            ->logs()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($log) => [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'status' => $log->status,
                'description' => $log->description,
                'actor' => $log->metadata['actor'] ?? 'system',
                'actor_role' => $log->metadata['role'] ?? 'system',
                'payload' => $log->payload,
                'created_at' => $log->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
