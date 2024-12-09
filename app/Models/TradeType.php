<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeType extends Model
{
    use HasFactory;

    /**
     * テーブル名を指定
     *
     * @var string
     */
    protected $table = 'trade_types';

    public function depoRealtimes()
    {
        return $this->hasMany(DepoRealtime::class, 'product_id');
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
        'trade_type',
        'name',
        'caption'
    ];

    /**
     * 日付属性のキャスト
     *
     * @var array
     */
    protected $dates = [
        'created_at'
    ];
}
