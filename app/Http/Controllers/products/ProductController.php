<?php

namespace App\Http\Controllers\products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(){
        try{
            $products = Product::all();
            $imagesProducts = ProductImage::where('product_id', $products->id)->get();
            $variantsProducts = ProductVariant::where('product_id', $products->id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Produits récupérés avec succès',
                'data' => $products,
                'images' => $imagesProducts,
                'variants' => $variantsProducts
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

    public function addProduct(ProductRequest $request){
        try{
            $validatedData = $request->validated();
            $validatedData['user_id'] = Auth::user()->id;

            //Creation des produits dans données de base dans la table produit.
            $product = Product::create($validatedData); 
            
            //creation des differentes variantes du produit.
            foreach($validatedData['variants'] as $variant){
                ProductVariant::create([
                    'product_id' => $product->id,
                    'taille' => $variant['taille'],
                    'couleur' => $variant['couleur'],
                    'sku' => $variant['sku'],
                    'stock' => $variant['stock'],
                ]);
            }

            //creation des differentes images du produit.
            foreach($validatedData['images'] as $image){
                ProductImage::create([
                    'product_id' => $product->id,
                    'url_image' => $image['url_image'],
                    'is_principal' => $image['is_principal'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produit ajouté avec succès',
                'data' => $product
            ], 201);
        
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }


    public function updateProduct(ProductRequest $request, $productId){
        try{
            $product = Product::findOrFail($productId);
            if(!$product){
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }
            $validatedData = $request->validated();
            $product->update($validatedData);

            //update des differentes variantes du produit.
            foreach($validatedData['variants'] as $variant){
                ProductVariant::updateOrCreate([
                    'product_id' => $product->id,
                    'taille' => $variant['taille'],
                    'couleur' => $variant['couleur'],
                    'sku' => $variant['sku'],
                    'stock' => $variant['stock'],
                ]);
            }

            //update des differentes images du produit.
            foreach($validatedData['images'] as $image){
                ProductImage::updateOrCreate([
                    'product_id' => $product->id,
                    'url_image' => $image['url_image'],
                    'is_principal' => $image['is_principal'],
                ]);
            }
            return response()->json([
                'success' => true,
                'message' => 'Produit mis à jour avec succès',
                'data' => $product
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteProduct($productId){
        try{
            $product = Product::find($productId);
            if(!$product){
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }
            $product->delete();
            return response()->json([
                'success' => true,
                'message' => 'Produit supprimé avec succès'
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }

    public function getProduct($productId){
        try{
            $product = Product::find($productId);
            if(!$product){
                return response()->json([
                    'success' => false,
                    'message' => 'Produit non trouvé'
                ], 404);
            }
            $imagesProducts = ProductImage::where('product_id', $product->id)->get();
            $variantsProducts = ProductVariant::where('product_id', $product->id)->get();
            return response()->json([
                'success' => true,
                'message' => 'Produit récupéré avec succès',
                'data' => $product,
                'images' => $imagesProducts,
                'variants' => $variantsProducts
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement : ' . $e->getMessage()
            ], 500);
        }
    }
}
