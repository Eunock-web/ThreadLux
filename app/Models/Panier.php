<?php

namespace App\Models;

use App\Models\Product;
use App\Models\ProductVariants;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Panier extends Model
{
    /** @use HasFactory<\Database\Factories\PanierFactory> */
    use HasFactory;

    protected $fillable = [
        'acheteur_id',
        'produit_id',
        'variant_id',
        'qte',
        'prix_unitaire',
    ];

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    public function produit()
    {
        return $this->belongsTo(Product::class, 'produit_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariants::class, 'variant_id');
    }
}
