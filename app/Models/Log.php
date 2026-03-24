<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    /** @use HasFactory<\Database\Factories\LogFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'transaction_id',
        'commande_id',
        'user_id',
        'event_type',
        'status',
        'description',
        'payload',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
