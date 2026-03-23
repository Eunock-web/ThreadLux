<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'transaction_id',
        'description',
        'payload',
        'statuts',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * The transaction linked to this log.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
