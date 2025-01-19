<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
     * IDから取引を1つ取得するメソッド
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
     * 該当する取引すべてを取得するメソッド
     *
     * @param array $params パラメータの配列
     * @return \Illuminate\Database\Eloquent\Collection 取得した取引のコレクション
     */
    public static function getTradings(array $params = [])
    {
        // デフォルトのパラメータを設定
        $currentDate = Carbon::now();
        $defaults = [
            'member_code' => null,
            'startDate' => null,
            'endDate' => $currentDate,
            'tradeTypes' => null,
            'sortColumn' => 'date',
            'sortDirection' => 'DESC',
            'status' => null,
        ];

        // デフォルト値と渡された値をマージ
        $params = array_merge($defaults, $params);

        // クエリの構築
        $query = self::with('tradeType')
            ->with(['user' => function($query) {
                $query->select('member_code', 'name');
            }])
            ->select('id', 'member_code', 'date', 'trade_type', 'amount');

        // メンバーコードで絞り込み
        if ($params['member_code']) {
            $query->where('member_code', $params['member_code']);
        }

        // 期間で絞り込み
        if ($params['startDate']) {
            $query->whereBetween('date', [$params['startDate'], $params['endDate']]);
        }
        
        // 取引種別で絞り込み
        if ($params['tradeTypes']) {
            $key = 'custom.' . $params['tradeTypes'];
            $query->whereIn('trade_type', config($key));
        }

        // statusで絞り込み
        if ($params['status']) {
            $query->where('status', $params['status']);
        }

        // 並べ替え
        $query->orderBy($params['sortColumn'], $params['sortDirection']);

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