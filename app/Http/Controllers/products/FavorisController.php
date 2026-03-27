<?php

namespace App\Http\Controllers\products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\products\Favoris;
use Illuminate\Support\Facades\Auth;
class FavorisController extends Controller
{
    public function addFavoris($variantId){
        try{
            $favoris = Favoris::where('acheteur_id', Auth::user()->id)->where('variant_id', $variantId)->first();
            if($favoris){
                $favoris->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Favoris supprimé avec succès'
                ], 200);
            }else{
                $favoris = Favoris::create([
                    'acheteur_id' => Auth::user()->id,
                    'variant_id' => $variantId,
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Favoris ajouté avec succès',
                    'data' => $favoris
                ], 200);
            }
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeFavoris($variantId){
        try{
            $favoris = Favoris::where('acheteur_id', Auth::user()->id)->where('variant_id', $variantId)->first();
            if($favoris){
                $favoris->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Favoris supprimé avec succès'
                ], 200);
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Favoris non trouvé',
                ], 404);
            }
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

    public function getFavoris(){
        try{
            $favoris = Favoris::where('acheteur_id', Auth::user()->id)->with('variant.product')->with('variant.images')->with('variant.product.vendeur')->with('variant.product.category')->with('variant.product.brand')->with('variant.product.reviews')->with('variant.product.reviews.user')->with('variant.product.reviews.user.profile')->with('variant.product.reviews.user.profile.avatar')->with('variant.product.reviews.user.profile.avatar.image')->with('variant.product.reviews.user.profile.avatar.image.imageable')->get();
            return response()->json([
                'success' => true,
                'message' => 'Favoris récupérés avec succès',
                'data' => $favoris
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

}
