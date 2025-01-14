<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeType extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'trade_types';

    public function depoRealtimes()
    {
        return $this->hasMany(DepoRealtime::class, 'product_id');
    }

    // Tradingsリレーションの追加
    public function tradingType()
    {
        return $this->hasMany(Trading::class, 'trade_type', 'trade_type');
    }

    protected $primaryKey = 'id';

    protected $fillable = ['trade_type', 'name', 'caption'];

    protected $dates = ['created_at'];

    /**
     * レコードを新規作成または更新するメソッド
     *
     * @param int $value
     * @return bool
     */
    public static function createOrUpdate($data)
    {
        return self::updateOrCreate(
            ['id' => $data['id'] ?? null],
            [
                'trade_type' => $data['trade_type'],
                'name' => $data['name'],
                'caption' => $data['caption']
            ]
        );
    }

    /**
     * 指定したtrade_typeが既に存在するか確認するメソッド
     *
     * @param int $value
     * @return bool
     */
    public static function tradeTypeExists($value)
    {
        return self::where('trade_type', $value)->exists();
    }

    /**
     * レコードを削除するメソッド
     *
     * @param int $id
     */
    public static function deleteById($id)
    {
        self::find($id)->delete();
    }
}
