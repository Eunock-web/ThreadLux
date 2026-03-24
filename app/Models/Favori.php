<?php

namespace App\Models;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favori extends Model
{
    /** @use HasFactory<\Database\Factories\FavoriFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'produit_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function produit()
    {
        return $this->belongsTo(Product::class, 'produit_id');
    }
}
