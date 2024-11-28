<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    public function depoRealtimes()
    {
        return $this->hasMany(DepoRealtime::class, 'product_id');
    }
}
