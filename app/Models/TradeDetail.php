<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeDetail extends Model
{
    use HasFactory;

    protected $table = 'trade_details';
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    protected $primaryKey = 'id';

    protected $fillable = [
        'trade_id',
        'product_id',
        'amount'
    ];

    protected $dates = [
        'created_at',
        'updated_at'
    ];

    /**
     * 取引詳細を取得するメソッド
     *
     * @param int $trade_id 取引ID
     * @return Model|null 取得詳細（「商品：セット数」の一覧）
     */
    public static function getTradeDetail($trade_id)
    {
        $details = self::with(['product' => function($query) {
                $query->select('id', 'name');
            }])
            ->where('trade_id', $trade_id)
            ->get();
        return $details;
    }
}
