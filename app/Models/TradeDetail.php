<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeDetail extends Model
{
    use HasFactory;

    /**
     * テーブル名を指定
     *
     * @var string
     */
    protected $table = 'trade_details';

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
        'trading_id',
        'product_id',
        'amount'
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
