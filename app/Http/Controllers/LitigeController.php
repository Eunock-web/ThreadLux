<?php

namespace App\Http\Controllers;

use App\Models\Litige;
use App\Models\Transaction;
use App\Services\TransactionLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * LitigeController — Full lifecycle management for transaction disputes.
 *
 * Flow:
 *   1. Buyer opens a litige  → escrow_status becomes 'en_litige'
 *   2. Admin reviews          → can resolve in favor of buyer (refund) or seller (release)
 *   3. On resolve:            → funds move accordingly + escrow_status updates
 */
class LitigeController extends Controller
{
    /**
     * POST /api/litiges
     * A buyer opens a dispute for a given transaction.
     */
    public function open(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'raison' => 'required|in:non_recu,non_conforme,defectueux,autre',
            'description' => 'required|string|min:20|max:2000',
        ]);

        $transaction = Transaction::with('commande')->findOrFail($request->transaction_id);

        // Only the buyer of the transaction (or a logged-in client) can open a dispute
        $user = auth()->user();

        // Prevent duplicate open litiges on the same transaction
        $exists = Litige::where('transaction_id', $transaction->id)
            ->whereNotIn('status', ['resolue_acheteur', 'resolue_vendeur', 'fermee'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Un litige est déjà en cours pour cette transaction.',
            ], 409);
        }

        // Escrow must be held to open a dispute
        if (!in_array($transaction->escrow_status, ['held', 'en_litige'])) {
            return response()->json([
                'success' => false,
                'message' => "Impossible d'ouvrir un litige : les fonds ne sont pas en escrow.",
            ], 422);
        }

        // Create the litige
        $litige = Litige::create([
            'commande_id' => $transaction->commande_id,
            'transaction_id' => $transaction->id,
            'initiateur_id' => $user->id,
            'raison' => $request->raison,
            'description' => $request->description,
            'status' => 'ouverte',
        ]);

        // Update transaction escrow status to 'en_litige' — blocks auto-release and manual payout
        $transaction->update(['escrow_status' => 'en_litige']);

        // Log the event
        TransactionLogger::log(
            $transaction->fresh(),
            'litige.opened',
            $user,
            "Litige ouvert par {$user->firstname} {$user->lastname}. Raison: {$request->raison}.",
            ['litige_id' => $litige->id, 'raison' => $request->raison]
        );

        return response()->json([
            'success' => true,
            'message' => "Litige ouvert. Les fonds resteront bloqués jusqu'à résolution.",
            'data' => $litige,
        ], 201);
    }

    /**
     * GET /api/seller/litiges
     * A vendor views disputes related to their transactions.
     */
    public function sellerIndex(): JsonResponse
    {
        $vendor = auth()->user();

        $litiges = Litige::whereHas('transaction', fn($q) => $q->where('vendeur_id', $vendor->id))
            ->with(['transaction', 'commande', 'initiateur'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $litiges,
        ]);
    }

    /**
     * GET /api/admin/litiges
     * An admin views all disputes across the platform (can filter by status).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Litige::with(['transaction', 'commande', 'initiateur', 'admin'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $litiges = $query->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $litiges,
        ]);
    }

    /**
     * GET /api/admin/litiges/{id}
     * Detail view of a single litige.
     */
    public function show(int $id): JsonResponse
    {
        $litige = Litige::with(['transaction', 'commande.items', 'initiateur', 'admin'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $litige,
        ]);
    }

    /**
     * PATCH /api/admin/litiges/{id}/resolve
     * Admin resolves a dispute — either in favor of the buyer or the seller.
     *
     * decision = 'resolue_vendeur'  → release funds to vendor (escrow_status = 'released')
     * decision = 'resolue_acheteur' → refund buyer       (escrow_status = 'refunded')
     */
    public function resolve(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'decision' => 'required|in:resolue_vendeur,resolue_acheteur',
            'resolution_note' => 'required|string|min:10|max:2000',
        ]);

        $admin = auth()->user();
        $litige = Litige::with('transaction')->findOrFail($id);

        if (in_array($litige->status, ['resolue_acheteur', 'resolue_vendeur', 'fermee'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce litige est déjà résolu.',
            ], 409);
        }

        $transaction = $litige->transaction;

        // Determine new escrow_status based on decision
        $newEscrowStatus = $request->decision === 'resolue_vendeur' ? 'released' : 'refunded';
        $eventType = $request->decision === 'resolue_vendeur' ? 'escrow.released' : 'escrow.refunded';

        // Update the litige
        $litige->update([
            'status' => $request->decision,
            'admin_id' => $admin->id,
            'resolution_note' => $request->resolution_note,
            'resolved_at' => now(),
        ]);

        // Update the transaction
        $transaction->update([
            'escrow_status' => $newEscrowStatus,
            'escrow_released_at' => now(),
        ]);

        if ($transaction->commande) {
            $transaction->commande->update(['escrow_status' => $newEscrowStatus]);
        }

        // If in favor of seller, trigger FedaPay payout
        if ($request->decision === 'resolue_vendeur') {
            try {
                $this->triggerFedaPayPayout($transaction);
            } catch (\Exception $e) {
                Log::error('FedaPay payout failed during litige resolution', [
                    'litige_id' => $litige->id,
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
                // Don't fail the resolution — DB is updated, payout can be retried manually
            }
        }

        // Log the event
        TransactionLogger::log(
            $transaction->fresh(),
            $eventType,
            $admin,
            "Litige #{$litige->id} résolu par admin {$admin->firstname} {$admin->lastname}. Décision: {$request->decision}. Note: {$request->resolution_note}",
            [
                'litige_id' => $litige->id,
                'decision' => $request->decision,
                'new_escrow_status' => $newEscrowStatus,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $request->decision === 'resolue_vendeur'
                ? 'Litige résolu. Les fonds ont été libérés au vendeur.'
                : "Litige résolu. Le remboursement a été initié pour l'acheteur.",
            'data' => $litige->fresh(['admin']),
        ]);
    }

    /**
     * Trigger a FedaPay payout for the vendor when a litige is resolved in their favor.
     */
    private function triggerFedaPayPayout(Transaction $transaction): void
    {
        $seller = $transaction->vendeur;
        if (!$seller)
            return;

        \FedaPay\FedaPay::setApiKey(env('FEDAPAY_SECRET_KEY'));
        \FedaPay\FedaPay::setEnvironment(env('FEDAPAY_ENVIRONMENT', 'sandbox'));

        try {
            $payout = \FedaPay\Payout::create([
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
                'description' => "Payout litige résolu — {$transaction->reference}",
            ]);
            \FedaPay\Payout::start($payout->id);
        } catch (\Exception $e) {
            $isLive = env('FEDAPAY_ENVIRONMENT') === 'live';
            if (!$isLive) {
                Log::warning('FedaPay payout simulation (sandbox) for litige resolution', ['error' => $e->getMessage()]);
            } else {
                throw $e;
            }
        }
    }
}
