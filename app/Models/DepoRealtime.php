<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepoRealtime extends Model
{
    protected $table = 'depo_realtime';
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
