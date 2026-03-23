<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * Initiate a payment via FedaPay without browser redirection (mobile-money flow).
     */
    // public function makeTransactionWithoutRedirection(TransactionRequest $request, int $produitId): JsonResponse
    // {
    //     $user = Auth::user();
    //     $validatedData = $request->validated();

    //     $product = \App\Models\Product::findOrFail($produitId);
    //     $amount = $product->price;

    //     \FedaPay\FedaPay::setApiKey(config('fedapay.secret_key'));
    //     \FedaPay\FedaPay::setEnvironment(config('fedapay.environment'));

    //     try {
    //         $collecte = \FedaPay\Transaction::create([
    //             'description' => $product->description ?: $validatedData['description'],
    //             'amount' => $amount,
    //             'currency' => ['iso' => $validatedData['currency']],
    //             'callback_url' => $validatedData['callback_url'],
    //             'customer' => [
    //                 'firstname' => $user->firstname,
    //                 'lastname' => $user->lastname,
    //                 'email' => $user->email,
    //             ],
    //             'custom_metadata' => [
    //                 'produit_id' => $product->id,
    //                 'acheteur_id' => $user->id,
    //             ],
    //         ]);

    //         $token = $collecte->generateToken()->token;

    //         $phone_number = [
    //             'number' => $user->phone,
    //             'country' => $user->country,
    //         ];

    //         $collecte->sendNowWithToken($validatedData['methode_payement'], $token, $phone_number);

    //         // Save transaction in our database
    //         Transaction::create([
    //             'user_id' => $user->id,
    //             'product_id' => $product->id,
    //             'amount' => $amount,
    //             'currency' => $validatedData['currency'],
    //             'description' => $product->description ?: $validatedData['description'],
    //             'methode_paiement' => $validatedData['methode_payement'],
    //             'status' => 'held',
    //             'fedapay_id' => $collecte->id,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'token' => $token,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors du paiement : ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Calculate the commission (4%) on a given amount.
     */
    public function calculeCommission(float $amount): float
    {
        return ($amount * 4) / 100;
    }

    /**
     * Handle FedaPay callback after a hosted-page payment.
     */
    public function callBack(Request $request, $transactionId): JsonResponse
    {
        // $status = $request->input('status');

        \FedaPay\FedaPay::setApiKey(config('fedapay.secret_key'));
        \FedaPay\FedaPay::setEnvironment(config('fedapay.environment'));

        try {
            // Find the local transaction by FedaPay id
            // $transaction = Transaction::where('fedapay_id', $transactionId)->first();

            $transaction = \FedaPay\Transaction::retrieve($transactionId);
            

            if ($transaction->status == 'approved') {

                return response()->json([
                    'success' => true,
                    'message' => 'Transaction approuvée',
                ], 200);
            }

            // Map FedaPay status to our internal status
            // $newStatus = match ($status) {
            //     'approved' => 'approved',
            //     'declined', 'canceled', 'refunded' => 'declined',
            //     default => 'held',
            // };

            // $transaction->update(['status' => $newStatus]);

            // return response()->json([
            //     'success' => true,
            //     'status' => $newStatus,
            // ]);
        } catch (\Exception $except) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du callback : ' . $except->getMessage(),
            ], 500);
        }
    }



    public function saveTransaction($transactionId){
        //Chercher l'id de la transactionn dans la table Log
        $transaction = Log::where('transaction_id', $transactionId)->first();
        //Valideer les données reçu de l'enpoint de tansaction
        $validatedData = $request->validated();
        if($transaction){
            //Enregistrer les données dans la table Transaction
            $transactionCreated = Transaction::create([
                'user_id' => Auth::user()->id,
                'product_id' => $validatedData->product_id,
                'amount' => $validatedData->amount,
                'currency' => $validatedData->currency,
                'description' => $validatedData->description,
                'methode_paiement' => $validatedData->methode_paiement,
                'status' => "held",
            ]);
        }           
    }
    
    //Fonction pour la Payout
    public function makePayout(){
        \Fedapay\Fedapay::setApikey(config('fedapay.secret_key'));
        \Fedapay\Fedapay::setEnvironment(config('fedapay.environment'));

        try{
            
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du payout : ' . $e->getMessage(),
            ], 500);
        }
    }
}
