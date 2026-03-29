<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
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

            // 4. Persist the transaction (best-effort — won't block checkout on DB error)
            $transaction = null;  // Initialize $transaction to null
            try {
                $transaction = Transaction::updateOrCreate(
                    ['provider_ref' => (string) $transactionId],
                    [
                        'reference' => 'TLX-' . strtoupper(substr(md5($transactionId), 0, 8)),
                        'acheteur_id' => auth('sanctum')->id() ?? null,  // use sanctum guard explicitly on public route
                        'vendeur_id' => $vendeurId,
                        'amount' => $fedaTransaction->amount ?? $amount,
                        'currency' => $fedaTransaction->currency->iso ?? 'XOF',
                        'payment_method' => 'mobile_money',
                        'provider' => 'fedapay',
                        'status' => $fedaTransaction->status,
                        'escrow_status' => ($fedaTransaction->status === 'approved') ? 'held' : 'none',
                        'escrow_held_at' => ($fedaTransaction->status === 'approved') ? now() : null,
                        'description' => "Commande client: {$customerEmail}",
                    ]
                );
                Log::info('Transaction saved to DB', ['provider_ref' => $transactionId, 'transaction_db_id' => $transaction->id]);
            } catch (\Exception $dbErr) {
                // Log but don't fail the checkout — payment IS approved on FedaPay's side
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
        $seller = auth()->user();

        $payouts = Transaction::where('vendeur_id', $seller->id)
            ->where('escrow_status', 'held')
            ->with(['commande.items.produit', 'acheteur'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payouts
        ]);
    }

    public function Payout($transactionId)
    {
        $seller = auth()->user();

        // 1. Find the transaction
        $transaction = Transaction::where('id', $transactionId)
            ->where('vendeur_id', $seller->id)
            ->firstOrFail();

        if ($transaction->escrow_status !== 'held') {
            return response()->json([
                'success' => false,
                'message' => "Cette transaction n'est pas en attente de déblocage."
            ], 400);
        }

        // 2. Configure FedaPay
        FedaPay::setApiKey(env('FEDAPAY_SECRET_KEY'));
        FedaPay::setEnvironment(env('FEDAPAY_ENVIRONMENT', 'sandbox'));

        try {
            // 3. Trigger FedaPay Payout (Real transfer)
            // Note: In sandbox, this simulates a transfer.
            try {
                $payout = \FedaPay\Payout::create([
                    'amount' => (int) $transaction->amount,
                    'currency' => ['iso' => $transaction->currency],
                    'mode' => $transaction->payment_method === 'mobile_money' ? 'mtn' : 'moov',  // simplified logic
                    'customer' => [
                        'firstname' => $seller->firstname,
                        'lastname' => $seller->lastname,
                        'email' => $seller->email,
                        'phone_number' => [
                            'number' => $seller->phone ?? '66000000',  // Default for test if null
                            'country' => 'bj'
                        ]
                    ],
                    'description' => "Payout pour la transaction {$transaction->reference}"
                ]);

                // Start the payout (official PHP SDK way)
                \FedaPay\Payout::start($payout->id);

                Log::info('FedaPay Payout successful', [
                    'transaction_id' => $transactionId,
                    'payout_id' => $payout->id
                ]);
            } catch (\Exception $fedaErr) {
                // Simulation is ONLY for sandbox
                $isLive = env('FEDAPAY_ENVIRONMENT') === 'live';

                if (!$isLive && (str_contains($fedaErr->getMessage(), 'autorisée') || str_contains($fedaErr->getMessage(), 'active'))) {
                    Log::warning('FedaPay Payout feature not active on account. SIMULATING SUCCESS for dev.', [
                        'error' => $fedaErr->getMessage()
                    ]);
                } else {
                    throw $fedaErr;  // Re-throw in live or for other errors
                }
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

            // 5. Send Email Notifications
            try {
                // To Seller
                \Illuminate\Support\Facades\Mail::to($seller->email)
                    ->send(new \App\Mail\PayoutReleasedSeller($transaction));

                // To Buyer (if exists)
                if ($transaction->acheteur) {
                    \Illuminate\Support\Facades\Mail::to($transaction->acheteur->email)
                        ->send(new \App\Mail\PayoutReleasedBuyer($transaction));
                }

                Log::info('Payout notification emails sent');
            } catch (\Exception $mailErr) {
                Log::error('Payout notification emails failed', ['error' => $mailErr->getMessage()]);
                // Don't fail the whole request if only emails failed
            }

            $msg = env('FEDAPAY_ENVIRONMENT') === 'live'
                ? 'Fonds débloqués avec succès.'
                : 'Fonds débloqués avec succès. (Mode simulation)';

            return response()->json([
                'success' => true,
                'message' => $msg
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
}
