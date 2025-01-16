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

    /**
     * IDから取引を取得するメソッド
     *
     * @param string|null $member_code
     * @param int $trade_id 取引ID
     * @return Model|null 取得した取引
     */
    public static function getTrade($trade_id, $member_code = null)
    {
        $query = self::with('tradeType')
            ->with(['user' => function($query) {
                $query->select('member_code', 'name');
            }])
            ->where('id', $trade_id);
    
        // member_codeで不正な取得をチェック
        if ($member_code) {
            $query->where('member_code', $member_code);
        }
    
        return $query->first();
    }

    /**
     * 条件に該当する取引を取得するメソッド
     *
     * @param string|null $member_code
     * @param string|null $startDate 開始日付
     * @param string|null $endDate 終了日付
     * @param string|null $sortColumn 並べ替え列
     * @param string $sortDirection 並べ替え方向 (ASC または DESC)
     * @param int $status statusカラム値
     * @return Collection 取得した取引のコレクション
     */
    public static function getTradings($member_code = null, $startDate = null, $endDate = null, $sortColumn = 'date', $sortDirection = 'DESC', $status = null)
    {
        $query = self::with('tradeType')
            ->with(['user' => function($query) {
                $query->select('member_code', 'name');
            }])
            ->select('id', 'member_code', 'date', 'trade_type', 'amount');

        // 期間で絞り込み
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        // メンバーコードで絞り込み
        if ($member_code) {
            $query->where('member_code', $member_code);
        }

        // statusで絞り込み
        if ($status) {
            $query->where('status', $status);
        }

        // 並べ替え
        $query->orderBy($sortColumn, $sortDirection);

        return $query->get();
    }

    /**
     * 最新の取引1件を取得するメソッド
     * @return Collection 最新の取引
     */
    public static function getLatestTrade()
    {
        return self::orderBy('date', 'DESC')->first();
    }
    

}