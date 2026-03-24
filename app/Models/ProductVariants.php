<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariants extends Model
{
    protected $fillable = [
        'product_id',
        'taille',
        'couleur',
        'sku',
        'stock'
    ];
}
