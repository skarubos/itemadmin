<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trading extends Model
{
    use HasFactory;

    protected $table = 'tradings';
    
    public function user()
    {
        return $this->belongsTo(User::class, 'member_code', 'member_code');
    }
    public function tradeType()
    {
        return $this->belongsTo(TradeType::class, 'trade_type', 'trade_type');
    }

    protected $primaryKey = 'id';
    protected $fillable = [
        'check_no',
        'member_code',
        'date',
        'trade_type',
        'amount',
        'status',
    ];
    protected $dates = ['created_at', 'updated_at'];

    /**
     * 指定したtrade_typeが存在するか確認するメソッド
     *
     * @param int $value
     * @return bool
     */
    public static function tradeTypeExists($value)
    {
        return self::where('trade_type', $value)->exists();
    }
}