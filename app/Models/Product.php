<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $table = 'products';

    public function depoRealtimes()
    {
        return $this->hasMany(DepoRealtime::class, 'product_id');
    }
    public function trade_details()
    {
        return $this->hasMany(TradeDetail::class, 'product_id');
    }

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'product_type'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * 商品種別を指定して、未使用の最小IDを取得するメソッド
     *
     * @param int $type 商品種別
     * @return int 新規商品のID（未使用の最小ID）
     */
    public static function getNewId($type)
    {
        // productsテーブルから使用中の最大IDを取得
        $maxId = self::where('id', '>', $type * 100)
            ->where('id', '<=', $type * 100 + 99)
            ->max('id');

        return $maxId + 1;
    }
}
