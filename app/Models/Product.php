<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    /**
     * テーブル名を指定
     *
     * @var string
     */
    protected $table = 'products';

    public function depoRealtimes()
    {
        return $this->hasMany(DepoRealtime::class, 'product_id');
    }
    public function trade_details()
    {
        return $this->hasMany(TradeDetail::class, 'product_id');
    }

    /**
     * 主キーのカラム名
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 複数代入可能な属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'product_type'
    ];

    /**
     * 日付属性のキャスト
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at'
    ];
}
