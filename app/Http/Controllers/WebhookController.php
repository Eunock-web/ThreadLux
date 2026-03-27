<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * FedaPay sends a POST to this endpoint after every payment event.
     * The request is signed with HMAC-SHA256 — we MUST verify it before trusting it.
     */
    public function handle(Request $request)
    {
        // 1. Signature verification
        $secret = env('FEDAPAY_WEBHOOK_SECRET');
        $payload = $request->getContent();
        $signature = $request->header('X-FedaPay-Signature');

        if ($secret && $signature) {
            $expected = 't=' . explode(',', $signature)[0] . ',v1=' . hash_hmac('sha256', $payload, $secret);
            if (!hash_equals($expected, $signature)) {
                Log::warning('FedaPay webhook: invalid signature', ['received' => $signature]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // 2. Parse event
        $event = json_decode($payload, true);
        $eventName = $event['name'] ?? 'unknown';
        $fedaData = $event['transaction'] ?? [];

        Log::info('FedaPay webhook received', [
            'event' => $eventName,
            'transaction_id' => $fedaData['id'] ?? null,
            'status' => $fedaData['status'] ?? null,
            'amount' => $fedaData['amount'] ?? null,
        ]);

        // 3. Update transaction status in the database
        if (!empty($fedaData['id'])) {
            $transaction = Transaction::where('provider_ref', (string) $fedaData['id'])->first();

            if ($transaction) {
                $transaction->status = $fedaData['status'] ?? $transaction->status;
                $transaction->save();

                Log::info('FedaPay webhook: transaction updated', [
                    'db_id' => $transaction->id,
                    'status' => $transaction->status,
                ]);
            } else {
                // Transaction not yet in DB (e.g. webhook arrived before verify call)
                Transaction::create([
                    'reference' => 'TLX-WH-' . strtoupper(substr(md5($fedaData['id']), 0, 8)),
                    'provider_ref' => (string) $fedaData['id'],
                    'amount' => $fedaData['amount'] ?? 0,
                    'currency' => $fedaData['currency']['iso'] ?? 'XOF',
                    'payment_method' => 'mobile_money',
                    'provider' => 'fedapay',
                    'status' => $fedaData['status'] ?? 'pending',
                    'description' => 'Créé via webhook FedaPay',
                ]);

                Log::info('FedaPay webhook: new transaction created from webhook', ['id' => $fedaData['id']]);
            }
        }

        // 4. Always return 200 so FedaPay stops retrying
        return response()->json(['received' => true], 200);
    }
}
