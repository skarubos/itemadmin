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

    /**
    * 商品名から商品IDを取得するメソッド
    * （新規商品の場合は、500番台のIDを取得してDBに新規レコード作成）
    *
    * @param string $name
    * @return int 新規商品のID（未使用の最小ID）
    */
    public static function getProductId($name) {
        // nameからproduct_idを取得
        $product = self::where('name', $name)->first();
        if ($product) {
            return $product->id;
        } else {
            // 新規の商品名の場合、その商品をproduct_idを500番台として仮登録
            // productsテーブルのidが500以上で最も大きいidを取得
            $maxId = self::where('id', '>', 500)->max('id');
            // 新しいidを決定
            $newId = $maxId ? $maxId + 1 : 501;
            // 新しいProductレコードを作成
            $product = new self;
            $product->id = $newId;
            $product->name = $name;
            $product->product_type = 5;
            $product->save();

            return $newId;
        }
    }
}
