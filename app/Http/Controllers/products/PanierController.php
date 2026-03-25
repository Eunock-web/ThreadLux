<?php

namespace App\Http\Controllers\products;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\PanierRequest;

class PanierController extends Controller
{
    public function addToCart(PanierRequest $request, $productId, $variantId){
        try{
            $product = Product::find($productId);
            if(!$product){
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }
            $variant = ProductVariant::where('product_id', $product->id)->where('id', $variantId)->first();
            if(!$variant){
                return response()->json([
                    'success' => false,
                    'message' => 'Variante non trouvée'
                ], 404);
            }
            $panier = Panier::create([
                'acheteur_id' => Auth::user()->id,
                'produit_id' => $product->id,
                'variant_id' => $variant->id,
                'qte' => $request->validated('qte'),
                'prix_unitaire' => $product->prix,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté au panier avec succès',
                'data' => $panier
            ], 201);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCart(){
        try{
            $panier = Panier::where('acheteur_id', Auth::user()->id)->with('product', 'variant')->get();
            return response()->json([
                'success' => true,
                'message' => 'Panier récupéré avec succès',
                'data' => $panier
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateCart($variantId, $qte){
        try{
            $panier = Panier::where('acheteur_id', Auth::user()->id)->where('variant_id', $variantId)->first();
            if(!$panier){
                return response()->json([
                    'success' => false,
                    'message' => 'Panier non trouvé'
                ], 404);
            }
            $panier->update([
                'qte' => $qte,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Panier mis à jour avec succès',
                'data' => $panier
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteCart($variantId){
        try{
            $panier = Panier::where('acheteur_id', Auth::user()->id)->where('variant_id', $variantId)->first();
            if(!$panier){
                return response()->json([
                    'success' => false,
                    'message' => 'Panier non trouvé'
                ], 404);
            }
            $panier->delete();
            return response()->json([
                'success' => true,
                'message' => 'Panier supprimé avec succès'
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }
}
