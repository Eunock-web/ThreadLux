<?php

namespace App\Models;

use App\Models\Commande;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avis extends Model
{
    /** @use HasFactory<\Database\Factories\AvisFactory> */
    use HasFactory;

    protected $table = 'avis';

    protected $fillable = [
        'user_id',
        'produit_id',
        'commande_id',
        'note',
        'titre',
        'description',
        'images',
        'is_verified',
        'is_approved',
    ];

    protected $casts = [
        'images' => 'array',
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function produit()
    {
        return $this->belongsTo(Product::class, 'produit_id');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }
}
