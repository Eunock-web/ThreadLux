<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;  // Added this import
use Illuminate\Database\Eloquent\Model;

class Litige extends Model
{
    /** @use HasFactory<\Database\Factories\LitigeFactory> */
    use HasFactory;

    protected $fillable = [
        'commande_id',
        'transaction_id',
        'initiateur_id',
        'raison',
        'description',
        'preuves',
        'status',
        'admin_id',
        'resolution_note',
        'resolved_at',
    ];

    protected $casts = [
        'preuves' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function initiateur()
    {
        return $this->belongsTo(User::class, 'initiateur_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
