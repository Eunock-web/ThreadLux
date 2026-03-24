<?php

namespace App\Models;

use App\Models\Address;
use App\Models\Avis;
use App\Models\CommandeItem;
use App\Models\Litige;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    /** @use HasFactory<\Database\Factories\CommandeFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'acheteur_id',
        'vendeur_id',
        'address_id',
        'montant_sous_total',
        'montant_livraison',
        'montant_total',
        'status',
        'escrow_status',
        'tracking_number',
        'note_acheteur',
        'livraison_estimee',
        'delivered_at',
    ];

    protected $casts = [
        'livraison_estimee' => 'date',
        'delivered_at' => 'datetime',
    ];

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function items()
    {
        return $this->hasMany(CommandeItem::class);
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function litige()
    {
        return $this->hasOne(Litige::class);
    }

    public function avis()
    {
        return $this->hasOne(Avis::class);
    }
}
