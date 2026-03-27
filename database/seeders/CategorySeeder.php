<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Streetwear',
            'Haute Couture',
            'Archive',
            'Accessoires',
            'Outerwear',
            'Essentials'
        ];

        foreach ($categories as $cat) {
            Categorie::updateOrCreate(
                ['slug' => Str::slug($cat)],
                [
                    'name' => $cat,
                    'imageUrl' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?q=80&w=1000&auto=format&fit=crop',
                ]
            );
        }
    }
}
