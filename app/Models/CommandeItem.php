<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandeItem extends Model
{
    /** @use HasFactory<\Database\Factories\CommandeItemFactory> */
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'produit_id',
        'variant_id',
        'nom_produit',
        'qte',
        'prix_unitaire',
        'prix_total',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
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
