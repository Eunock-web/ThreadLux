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
                        'acheteur_id' => auth()->id() ?? null,  // nullable: guest checkout
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
                        'acheteur_id' => auth()->id() ?? null,
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

    public function Payout(){
        
    }

}
