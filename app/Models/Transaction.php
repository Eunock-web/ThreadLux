<?php

namespace App\Models;

use App\Models\Commande;
use App\Models\Log;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'acheteur_id',
        'vendeur_id',
        'commande_id',
        'amount',
        'currency',
        'payment_method',
        'provider',
        'provider_ref',
        'status',
        'escrow_status',
        'escrow_held_at',
        'escrow_released_at',
        'description',
    ];

    protected $casts = [
        'escrow_held_at' => 'datetime',
        'escrow_released_at' => 'datetime',
    ];

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function logs()
    {
        return $this->hasMany(Log::class);
    }
}
