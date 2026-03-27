<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'categorie_id',
        'name',
        'description',
        'slug',
        'promo',
        'prix',
        'origine',
        'coupe',
        'hasVariants',
        'stock_global'
    ];

    public function category()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariants::class);
    }

    public function paniers()
    {
        return $this->hasMany(Panier::class, 'produit_id');
    }

    public function commandeItems()
    {
        return $this->hasMany(CommandeItem::class, 'produit_id');
    }

    public function avis()
    {
        return $this->hasMany(Avis::class, 'produit_id');
    }

    public function favoris()
    {
        return $this->hasMany(Favori::class, 'produit_id');
    }

    public function images()
    {
        return $this->hasMany(productImage::class, 'product_id');
    }
}
