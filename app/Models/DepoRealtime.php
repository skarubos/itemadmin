<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepoRealtime extends Model
{
    use HasFactory;

    /**
     * テーブル名を指定
     *
     * @var string
     */
    protected $table = 'depo_realtime';
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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
        'member_code',
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
