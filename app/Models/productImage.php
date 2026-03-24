<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class productImage extends Model
{
    protected $fillable = [
        'product_id',
        'url_image',
        'is_principal'
    ];
}
