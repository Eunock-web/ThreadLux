<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\productImage;
use App\Models\ProductVariants;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Archival Heavy Hoodie',
                'description' => 'Un sweat à capuche premium en coton lourd, pièce d\'archive.',
                'prix' => 145.00,
                'origine' => 'Portugal',
                'coupe' => 'Oversize',
                'images' => [
                    'https://images.unsplash.com/photo-1556821840-3a63f95609a7?q=80&w=1000&auto=format&fit=crop',
                ]
            ],
            [
                'name' => 'Neon Pulse Runner',
                'description' => 'Sneakers futuristes avec amorti réactif et design néon.',
                'prix' => 180.00,
                'origine' => 'Italy',
                'coupe' => 'Sport',
                'images' => [
                    'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=1000&auto=format&fit=crop',
                ]
            ],
            [
                'name' => 'Biker Moto Jacket',
                'description' => 'Veste en cuir véritable avec détails asymétriques.',
                'prix' => 295.00,
                'origine' => 'France',
                'coupe' => 'Regular',
                'images' => [
                    'https://images.unsplash.com/photo-1591369822096-ffd140ec948f?q=80&w=1000&auto=format&fit=crop',
                ]
            ],
            [
                'name' => 'Signature Tee Pack',
                'description' => 'Pack de t-shirts essentiels en coton égyptien.',
                'prix' => 65.00,
                'origine' => 'Egypt',
                'coupe' => 'Slim Fit',
                'images' => [
                    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?q=80&w=1000&auto=format&fit=crop',
                ]
            ],
            [
                'name' => 'Orbit Crossbody',
                'description' => 'Sac bandoulière minimaliste en cuir argenté.',
                'prix' => 85.00,
                'origine' => 'Spain',
                'coupe' => 'One Size',
                'images' => [
                    'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?q=80&w=1000&auto=format&fit=crop',
                ]
            ]
        ];

        $user = \App\Models\User::first();
        $categories = \App\Models\Categorie::all();

        foreach ($products as $pData) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($pData['name'])],
                [
                    'user_id' => $user->id,
                    'categorie_id' => $categories->random()->id,
                    'name' => $pData['name'],
                    'description' => $pData['description'],
                    'prix' => $pData['prix'],
                    'origine' => $pData['origine'],
                    'coupe' => $pData['coupe'],
                    'hasVariants' => true,
                    'stock_global' => 100,
                ]
            );

            productImage::updateOrCreate(
                ['product_id' => $product->id, 'is_principal' => true],
                ['url_image' => $pData['images'][0]]
            );

            // Create some variants
            $sizes = ['S', 'M', 'L', 'XL'];
            foreach ($sizes as $size) {
                ProductVariants::updateOrCreate(
                    ['product_id' => $product->id, 'taille' => $size, 'couleur' => 'Default'],
                    [
                        'sku' => strtoupper(substr($product->name, 0, 3)) . '-' . $size . '-' . $product->id,
                        'stock' => 10,
                    ]
                );
            }
        }
    }
}
