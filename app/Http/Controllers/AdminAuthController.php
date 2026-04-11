<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * AdminAuthController — Login endpoint reserved for vendeur/seller accounts.
 * Regular clients cannot authenticate through this endpoint.
 */
class AdminAuthController extends Controller
{
    /**
     * Authenticate a vendor (vendeur) account.
     * Returns a Sanctum token only if the user has role 'vendeur'.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::where('email', $request->email)->first();

        // Verify credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        // Ensure the account is a vendor (or admin) — block regular clients
        if (!in_array($user->role, ['vendeur', 'admin'])) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé. Cet espace est réservé aux vendeurs.',
            ], 403);
        }

        // Revoke previous sessions
        $user->tokens()->delete();
        $token = $user->createToken('vendor-dashboard-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion vendeur réussie.',
            'token' => $token,
            'user' => $user,
        ]);
    }
}
