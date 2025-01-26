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

    /**
     * 任意のユーザーの合計実績を取得
     *
     * @param string $memberCode
     * @return int 取引数量の合計
     */
    public static function getSalesForMember($memberCode)
    {
        // 今年の最初の日付を取得
        $startDate = Carbon::now()->startOfYear();
        // 注文対象となる取引タイプを取得
        $tradeTypes = config('custom.sales_tradeTypes');

        $sales = self::where('member_code', $memberCode)
            ->where('date', '>=', $startDate)
            ->whereIn('trade_type', $tradeTypes)
            ->sum('amount');
        return $sales;
    }

    /**
     * 任意のユーザーの最新の注文を検索
     *
     * @param string $memberCode
     * @return int 取引ID
     */
    public static function getLatestForMember($memberCode) {
        // 最新の注文にカウントする取引タイプの配列（移動20を除く）
        $tradeTypes = array_diff(config('custom.sales_tradeTypes'), [20]);
        // 最新注文の対象となる最小移動合計セット数
        $idouMinSet = config('custom.idou_minSet');
        $latest = self::where('member_code', $memberCode)
            ->where(function($query) use ($tradeTypes, $idouMinSet) {
                $query->whereIn('trade_type', $tradeTypes)
                    ->orWhere(function($query) use ($idouMinSet) {
                        $query->where('trade_type', 20)
                            ->where('amount', '>=', $idouMinSet);
                    });
            })
            ->orderBy('date', 'DESC')
            ->select('id')
            ->first();
        return $latest;
    }

    /**
     * 指定されたメンバーコードのユーザー情報と、設定年数分の年間売上詳細を取得する
     *
     * @param string $memberCode
     * @return array 以下のキーを持つ連想配列を返す：
     *               - 'years': 取得対象となった年（西暦）の配列
     *               - 'yearlySales': 年ごと、月ごとの売上数量を格納した2次元配列
     *               - 'totals': 年ごとの売上数量の合計を格納した配列
     */
    public static function getSalesDetailForMember($memberCode)
    {
        // 表示する年数を取得
        $years = config('custom.sales_howManyYears');

        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;

        // 取得する年の西暦を取得
        $yearArr = [];
        for ($i = 0; $i < $years; $i++) {
            $yearArr[] = $currentYear - $i;
        }
        $yearArr = array_reverse($yearArr);

        // 月ごとの実績を取得
        $yearlySales = [];
        $totals = array_fill(0, $years, 0);
        for ($month = 1; $month <= 12; $month++) {
            $monthlySales = [];
            foreach ($yearArr as $i => $year) {
                $monthlySum = null;
                if (!($year === $currentYear && $month > $currentMonth)) {
                    $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                    $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                    $monthlySum = self::where('member_code', $memberCode)
                        ->whereBetween('date', [$startOfMonth, $endOfMonth])
                        ->whereIn('trade_type', config('custom.sales_tradeTypes'))
                        ->sum('amount');
                }
                $monthlySales[] = $monthlySum;
                $totals[$i] += $monthlySum;
            }
            $yearlySales[] = $monthlySales;
        }

        return [
            'years' => $yearArr,
            'yearlySales' => $yearlySales,
            'totals' => $totals,
        ];
    }
    
    public static function getMonthlySales($users)
    {
        // 取得期間の設定：現在から過去12か月
        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
    
        // 月のリストを作成
        $months = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $months[] = $currentDate->format('Y-m');
            $currentDate->addMonth();
        }
    
        // 結果を格納する配列の初期化
        $result = [];
        $totals = [];
        foreach ($months as $month) {
            $result[$month] = [];
            $totals[$month] = [0, 0]; // [合計値, member_code:3851の合計値]
        }
    
        // ユーザーのリストを取得
        $memberNames = [];
        foreach ($users as $user) {
            $memberNames[$user->member_code] = $user->name;
        }
    
        // データベースからデータを取得
        $tradings = self::selectRaw("
                member_code,
                DATE_FORMAT(date, '%Y-%m') as month,
                SUM(amount) as total_amount
            ")
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('trade_type', config('custom.sales_tradeTypes'))
            ->whereIn('member_code', array_merge(array_keys($memberNames), [3851]))
            ->groupBy('member_code', 'month')
            ->get();
    
        // データの集計
        foreach ($tradings as $trading) {
            $month = $trading->month;
            $code = $trading->member_code;
            $amount = $trading->total_amount;
    
            if ($code == 3851) {
                // member_code 3851 の取引は $totals の 2 番目の要素に追加
                $totals[$month][1] += $amount;
            } else {
                $name = $memberNames[$code];
    
                // ユーザーごとの合計を設定
                $result[$month][$name] = $amount;
    
                // $totals の 1 番目の要素に追加
                $totals[$month][0] += $amount;
            }
        }
    
        // ユーザーデータを値の大きい順にソート
        foreach ($result as $month => &$data) {
            arsort($data);
        }

        return [
            'monthlySales' => $result,
            'totals' => $totals,
        ];
    }
    
}