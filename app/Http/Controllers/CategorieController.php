<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

    public function index()
    {
        try {
            $categories = \App\Models\Categorie::all();
            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage()
            ], 500);
        }
    }
