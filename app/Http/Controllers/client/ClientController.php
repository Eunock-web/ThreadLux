<?php

namespace App\Http\Controllers\client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

// use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function userInfo()
    {
        try {
            // Récupérer l'utilisateur connecté avec ses relations (adresses, paniers, etc.)
            $user = Auth::user()->load(['addresses']);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Données récupérées avec succès',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }


}
