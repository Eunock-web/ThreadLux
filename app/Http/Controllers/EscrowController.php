<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EscrowController extends Controller
{
    /**
     * Get pending payouts for the authenticated seller.
     */
    public function index()
    {
        $seller = Auth::user();

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

    /**
     * Release funds for a specific transaction.
     */
    public function release($transactionId)
    {
        $seller = Auth::user();

        $transaction = Transaction::where('id', $transactionId)
            ->where('vendeur_id', $seller->id)
            ->firstOrFail();

        if ($transaction->escrow_status !== 'held') {
            return response()->json([
                'success' => false,
                'message' => "Cette transaction n'est pas en attente de déblocage."
            ], 400);
        }

        try {
            // Update Escrow Status
            $transaction->update([
                'escrow_status' => 'released',
                'escrow_released_at' => now(),
                'status' => 'paid'  // Or a specific status like 'transferred'
            ]);

            // Update associated order
            if ($transaction->commande) {
                $transaction->commande->update(['escrow_status' => 'released']);
            }

            Log::info('Escrow funds released by seller', [
                'transaction_id' => $transactionId,
                'seller_id' => $seller->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fonds débloqués avec succès. Le virement est en cours.'
            ]);
        } catch (\Exception $e) {
            Log::error('Escrow release failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déblocage des fonds.'
            ], 500);
        }
    }
}
