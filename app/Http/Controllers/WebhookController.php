<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Handle incoming FedaPay webhook events.
     *
     * FedaPay sends a HMAC-SHA256 signature in the X-FEDAPAY-SIGNATURE header.
     * We verify it before processing.
     */
    public function webhook(Request $request): JsonResponse
    {
        $secret = config('fedapay.webhook_secret');
        $payload = $request->getContent();
        $signature = $request->header('X-FEDAPAY-SIGNATURE');

        // 1. Verify signature
        if (!$this->isSignatureValid($payload, $signature, $secret)) {
            return response()->json(['message' => 'Signature invalide'], 401);
        }

        // 2. Parse the payload
        $data = json_decode($payload, true);

        if (!isset($data['name'], $data['data'])) {
            return response()->json(['message' => 'Payload invalide'], 400);
        }

        // $eventName = $data['name'];  // e.g. "transaction.approved"
        // $fedapayData = $data['data']['object'] ?? [];
        // $fedapayId = $fedapayData['id'] ?? null;

        if (!$fedapayId) {
            return response()->json(['message' => 'ID FedaPay manquant'], 400);
        }

        if($data->entity == "transaction" && $data->status == "approved"){
            $transactionId = $data->id; 
        }

        // // 3. Find the local transaction
        // // $transaction = Transaction::where('fedapay_id', $fedapayId)->first();

        // if (!$transaction) {
        //     // Not our transaction — acknowledge and ignore
        //     return response()->json(['message' => 'Transaction inconnue'], 200);
        // }

        // // 4. Map the event to a local status
        // $newStatus = match (true) {
        //     str_contains($eventName, 'approved') => 'approved',
        //     str_contains($eventName, 'declined') => 'declined',
        //     str_contains($eventName, 'canceled') => 'declined',
        //     str_contains($eventName, 'refunded') => 'declined',
        //     default => $transaction->status,
        // };

        // $transaction->update(['status' => $newStatus]);

        // 5. Log the event
        Log::create([
            'transaction_id' => $transaction->id,
            'description' => 'Événement FedaPay reçu : ' . $eventName,
            'payload' => $data,
            'statuts' => $data->status,
            'metadata' => json_encode($fedapayData),
        ]);

        return response()->json(['message' => 'Webhook traité avec succès'], 200);
    }

    /**
     * Verify the HMAC-SHA256 signature sent by FedaPay.
     */
    private function isSignatureValid(string $payload, ?string $signature, ?string $secret): bool
    {
        if (!$signature || !$secret) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
