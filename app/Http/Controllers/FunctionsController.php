<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\User;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Models\Trading;
use App\Models\TradeDetail;
use App\Models\TradeType;
use App\Models\RefreshLog;
use Carbon\Carbon;
use Exception;

class FunctionsController extends Controller
{
    /**
     * 年間実績&最新注文&資格手当を更新
     *（特定のユーザーのみ OR 全ユーザー　両対応）
     *
     * @param $codeOrUsers 特定のユーザーのmember_code OR 全ユーザーのコレクション
     */
    public function refresh($codeOrUsers) {
        if (is_numeric($codeOrUsers)) {
            // 引数が単体（$memberCode）の場合、単一ユーザーの処理を実行
            $user = User::where('member_code', $codeOrUsers)->first();
            // 年間実績
            $params = $this->getSalesParam();
            $sales = $this->getSalesForMember($codeOrUsers, $params['startDate'], $params['tradeTypes']);
            $user->sales = $sales;
            // 最新注文
            $params = $this->getLatestParam();
            $latest = $this->getLatestForMember($codeOrUsers, $params['tradeTypes'], $params['idouMinSet']);
            $user->latest_trade = $latest ? $latest->id : null;
            // 資格手当(グルーブに所属しておりリーダーでもない場合、そのリーダーの資格手当を再計算)
            if ($user->sub_number !== 0 && $user->sub_leader == 0) {
                $subLeaderValue = $user->sub_number;
                // 該当するサブリーダー$subLeaderを取得
                $subLeader = User::where('sub_leader', $subLeaderValue)->first();
                $params = $this->getSubParam();
                $num = $this->getSubForMember($subLeaderValue, $params['startDate'], $params['tradeTypes'], $params['subMinSet']);

                // 該当のサブリーダーのsub_nowカラムを更新、上限を500に設定
                $subLeader->sub_now = min($num*100, 500);
                $subLeader->save();
            }
            $user->save();
        } elseif (get_class($codeOrUsers) === 'Illuminate\Database\Eloquent\Collection') {
            // 引数がコレクション（$users）の場合、各ユーザーに対して処理を実行
            $this->refresh_sales($codeOrUsers);
            $this->refresh_latest($codeOrUsers);
            $this->refresh_sub($codeOrUsers);
        } else {
            dd($codeOrUsers);
            // 想定外の引数の場合
            throw new Exception('更新すべき対象として、想定外の引数が渡されました。' . $codeOrUsers);
        }
    }

    // 全ユーザーの実績を更新
    public function refresh_sales($users) {
        $params = $this->getSalesParam();
        foreach ($users as $user) {
            $sales = $this->getSalesForMember($user->member_code, $params['startDate'], $params['tradeTypes']);
            $user->sales = $sales;
            $user->save();
        }
    }
    // 全ユーザーの最新取引を更新
    public function refresh_latest($users) {
        $params = $this->getLatestParam();
        foreach ($users as $user) {
            $latest = $this->getLatestForMember($user->member_code, $params['tradeTypes'], $params['idouMinSet']);
            $user->latest_trade = $latest ? $latest->id : null;
            $user->save();
        }
    }
    // 全サブリーダーの資格手当を更新
    public function refresh_sub($users) {
        $params = $this->getSubParam();

        // sub_leaderの値が0でないユーザーを取得
        $usersWithSubLeader = $users->filter(function ($user) {
            return $user->sub_leader !== 0;
        });
        // $subs配列にsub_leaderの値を格納
        $subs = $usersWithSubLeader->pluck('sub_leader')->toArray();

        foreach ($usersWithSubLeader as $user) {
            // 過去6ヶ月の実績を持つユーザーの数を取得
            $subLeaderValue = $user->sub_leader;
            $num = $this->getSubForMember($subLeaderValue, $params['startDate'], $params['tradeTypes'], $params['subMinSet']);

            // sub_nowカラムを更新、上限を500に設定
            $user->sub_now = min($num*100, 500);
            $user->save();
        }
    }

    /**
     * 任意のユーザーの預け記録を更新
     * 現在合計預けセット数＆DepoRealtimeテーブルを更新
     *
     * @param string $memberCode
     * @param int $tradeType 
     * @param int $amount 取引合計セット数（更新の場合は差分セット数　【例】旧40,新20：-20）
     * @param array $details 取引詳細
     * @param int $add (1)新規・更新、(-1)削除
     */
    public function saveDepoForMember($memberCode, $tradeType, $amount, $details, $add) {
        // 預入れor預出しの判定
        // $tradeTypeの値をconfig/custom.phpのdepo_tradeTypesと照らし合わせて、$signを正負とする
        $depoTradeTypesIn = array_fill_keys(config('custom.depo_tradeTypesIn'), 1 * $add);
        $depoTradeTypesOut = array_fill_keys(config('custom.depo_tradeTypesOut'), -1 * $add);
        $tradeTypes = $depoTradeTypesIn + $depoTradeTypesOut;
        $sign = $tradeTypes[$tradeType] ?? throw new Exception("取引タイプが不正です。（預入れor預出し）");

        $user = User::where('member_code', $memberCode)->first();
        $user->depo_status += $amount * $sign;
        $user->save();

        // DepoRealtimeの更新
        foreach ($details as $detail) {
            $depoRealtime = DepoRealtime::firstOrNew([
                'member_code' => $memberCode,
                'product_id' => $detail['product_id']
            ]);
            $depoRealtime->amount += $detail['amount'] * $sign;
            $depoRealtime->save();
        }
    }

    private function getSalesParam() {
        // 今年の最初の日付を取得
        $startOfYear = Carbon::now()->startOfYear();
        // 注文対象となる取引タイプを取得
        $tradeTypes = config('custom.sales_tradeTypes');
        return [
            'startDate' => $startOfYear,
            'tradeTypes' => $tradeTypes,
        ];
    }
    private function getLatestParam() {
        // 最新の注文にカウントする取引タイプの配列（移動20を除く）
        $tradeTypes = array_diff(config('custom.sales_tradeTypes'), [20]);
        // 最新注文の対象となる最小移動合計セット数
        $idouMinSet = config('custom.idou_minSet');
        return [
            'tradeTypes' => $tradeTypes,
            'idouMinSet' => $idouMinSet,
        ];
    }
    private function getSubParam() {
        // 資格手当の対象となる最初の日付$startDateを取得
        $currentDate = Carbon::now();
        $startDate = $currentDate->copy()->subMonths(config('custom.sub_monthsCovered'))->addDay();
        // 資格手当の対象となる取引タイプを取得
        $tradeTypes = config('custom.sales_tradeTypesEigyosho');
        // 資格手当の対象となる最小合計セット数を取得
        $subMinSet = config('custom.sub_minSet');
        return [
            'startDate' => $startDate,
            'tradeTypes' => $tradeTypes,
            'subMinSet' => $subMinSet,
        ];
    }

    /**
     * 任意のユーザーの合計実績を取得
     *
     * @param string $memberCode
     * @param mixed $startDate 集計開始日
     * @param array $tradeTypes 実績カウントする取引タイプの配列
     * @return int 取引数量の合計
     */
    private function getSalesForMember($memberCode, $startDate, $tradeTypes) {
        $sales = Trading::where('member_code', $memberCode)
            ->where('date', '>=', $startDate)
            ->whereIn('trade_type', $tradeTypes)
            ->sum('amount');
        return $sales;
    }

    /**
     * 任意のユーザーの最新の注文を検索
     *
     * @param string $memberCode
     * @param array $tradeTypes 最新の注文にカウントする取引タイプの配列（必ず移動20を除くこと！）
     * @param int $idouMinSet 最新注文の対象となる最小移動合計セット数
     * @return int 取引ID
     */
    private function getLatestForMember($memberCode, $tradeTypes, $idouMinSet) {
        $latest = Trading::where('member_code', $memberCode)
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
     * 任意のサブリーダーの資格手当を取得
     *
     * @param string $subLeaderValue グループナンバー
     * @param mixed $startDate 資格手当の対象となる最初の日付
     * @param array $tradeTypes 実績対象の取引タイプの配列
     * @param int $subMinSet 資格手当対象となる最小合計セット数
     * @return int 資格手当対象となる傘下営業所の人数
     */
    public function getSubForMember($subLeaderValue, $startDate, $tradeTypes, $subMinSet) {
        $num = User::where('sub_number', $subLeaderValue)
                ->whereHas('tradings', function ($query) use ($startDate, $tradeTypes, $subMinSet) {
                    $query->where('date', '>=', $startDate)
                        ->whereIn('trade_type', $tradeTypes)
                        ->havingRaw('SUM(amount) > ?', [$subMinSet]);
                })
                ->count();
        return $num;
    }


    /**
     * 指定されたメンバーコードのユーザー情報と、そのユーザーの預けの詳細情報を取得する
     *
     * @param string $memberCode
     * @return array 以下のキーを持つ連想配列を返す：
     *               - 'user': ユーザー情報 (member_code, name, depo_status) を含むオブジェクト
     *               - 'details': 預けの詳細情報を格納したコレクション。各要素は DepoRealtime モデルのインスタンスで、
     *                            product リレーション（商品情報）を含む。
     *
     * @throws Exception データベース操作中にエラーが発生した場合
     */
    public function get_depo_detail($memberCode){
        $user = User::where('member_code', $memberCode)
            ->select('member_code', 'name', 'depo_status')
            ->first();
        $details = DepoRealtime::with('product')
            ->where('member_code', $memberCode)
            ->where('amount', '!=', 0)
            ->orderBy('product_id', 'ASC')
            ->get();
        return [
            'user' => $user,
            'details' => $details,
        ];
    }

    /**
     * 指定されたメンバーコードのユーザー情報と、指定された年数分の年間売上詳細を取得する
     *
     * @param string $memberCode
     * @param int $years 取得する年数（例：3 を指定すると、過去3年分のデータを取得）
     * @return array 以下のキーを持つ連想配列を返す：
     *               - 'user': ユーザー情報 (name, member_code, sales, depo_status) を含むオブジェクト
     *               - 'years': 取得対象となった年（西暦）の配列
     *               - 'yearlySales': 年ごと、月ごとの売上数量を格納した2次元配列
     *               - 'totals': 年ごとの売上数量の合計を格納した配列
     */
    public function get_sales_detail($memberCode, $years){
        $user = User::where('member_code', $memberCode)
            ->select('name', 'member_code', 'sales', 'depo_status')
            ->first();

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
                    $monthlySum = Trading::where('member_code', $memberCode)
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
            'user' => $user,
            'years' => $yearArr,
            'yearlySales' => $yearlySales,
            'totals' => $totals,
        ];
    }

    /**
     * 指定された年数分の年月を'YYYYMM'形式で配列にして返す
     * 0年分が指定された場合は、現在の年月のみを返す
     * 
     * @param int $years 取得する年数
     */
    public function getMonthArr($years) {
        if ($years == 0) {
            $month = Carbon::now()->format('Ym');
            return $month;
        } else {
            $months = [];
            $currentMonth = Carbon::now();
            for ($i = 0; $i < 12 * $years; $i++) {
                $months[] = $currentMonth->format('Ym');
                $currentMonth->subMonth();
            }
            return $months;
        }
    }

    public function getRefreshLog(){
        $logs = [
            'refresh_sub' => RefreshLog::where('method', 'refresh_sub')->orderBy('created_at', 'DESC')->first(),
            'refresh' => RefreshLog::where('method', 'refresh')->orderBy('created_at', 'DESC')->first(),
            'scrape' => RefreshLog::where('method', 'scrape')->orderBy('created_at', 'DESC')->first(),
        ];
    
        foreach ($logs as $key => $log) {
            if (!$log) {
                $logs[$key] = "自動更新ログが存在しません。";
            }
        }
    
        return $logs;
    }


    /**
     * 「月間取引一覧」ページから未登録の商品取引の一覧を取得、配列として返す
     * 
     * @param $crawler DomCrawlerで解析されたHTMLデータ
     * @param int $month 参照すべき年月(取引が未登録かどうかの判定に使用)
     */
    public function getNewTrade($crawler, $month) {
        // tbody要素から取引一覧を取得
        $tradeData = [];
        $crawler->filter('tbody.table-hover tr')->each(function ($node) use (&$tradeData) {
            $tradeData[] = [
                'link' => $node->filter('td')->eq(1)->filter('a')->attr('href'),
                'date' => $node->filter('td')->eq(3)->text(),
                'name' => $node->filter('td')->eq(6)->text(),
                'sales' => $node->filter('td')->eq(7)->text(),
                'in' => $node->filter('td')->eq(8)->text(),
                'out' => $node->filter('td')->eq(9)->text(),
            ];
        });
    
        // sales, in, outの3つ全てに値が存在しない要素をフィルタリング
        $tradeData = array_filter($tradeData, function($row) {
            return !empty($row['sales']) || !empty($row['in']) || !empty($row['out']);
        });
    
        // 指定された月の全取引のcheck_noを配列として取得
        $arr = $this->getJutyunoArr($month);
    
        // 未登録の取引を抜粋、必要な属性を設定して新しい配列に入れ替える
        $newTrade = [];
        foreach ($tradeData as $row) {
            // 'link'から'jutyuno'を抽出して追加
            $row['jutyuno'] = $this->getNo($row['link']);
            // 既に登録されている取引をスキップ
            if (in_array($row['jutyuno'], $arr)) {
                continue;
            }
            if ($row['sales']) {
                $newTrade[] = $this->setTradeAttributes($row, 'sales');
            }
            if ($row['in']) {
                $newTrade[] = $this->setTradeAttributes($row, 'in');
            }
            if ($row['out']) {
                $newTrade[] = $this->setTradeAttributes($row, 'out');
            }
        }
    
        // NULLを除外して結果を返す
        return array_filter($newTrade);
    }

    public function getJutyunoArr($month)
    {
        // 'YYYYMM'から'YYYY-MM'形式に変換
        $formattedMonth = substr($month, 0, 4) . '-' . substr($month, 4, 2);
    
        $arr = Trading::whereNotNull('check_no')
            ->where('date', 'like', $formattedMonth . '%') // 指定された月で絞り込み
            ->pluck('check_no')
            ->toArray();
    
        return $arr;
    }
    public function getNo($string) {
        $string = substr($string, strlen(config('secure.url_forRemove')));
        $string = substr($string, 0, -15);
        return $string;
    }
    public function setTradeAttributes($row, $type) {
        // 取引種別に関係なく共通の属性を設定
        $trade = [
            'check_no' => $row['jutyuno'],
            'link' => $row['link'],
            'date' => $row['date'],
            'member_code' => $this->getMemberCode($row['name']),
        ];
    
        // amount, trade_type属性を設定（判明する範囲で）
        switch ($type) {
            case 'sales': // 注文
                $trade['type'] = 'sales';
                $trade['amount'] = $row['sales'];
                $trade['trade_type'] = $this->getTradeType($trade['member_code'], 110, 10);
                break;
            case 'in': // 預入れ
                $trade['type'] = 'in';
                $trade['amount'] = $row['in'];
                $trade['trade_type'] = $this->getTradeType($trade['member_code'], 111, 11);
                break;
            case 'out': // 預け出し
                $trade['type'] = 'out';
                $trade['amount'] = $row['out'];
                $trade['trade_type'] = $this->getTradeType($trade['member_code'], 121, 21);
                break;
        }
    
        return $trade;
    }    
    public function getMemberCode($name) {
        if ($name == '店頭販売') {
            return 3851;
        }
        // ユーザーを検索し、見つからない場合は代理店のmember_codeを返す
        $user = User::where('name', $name)->first();
        return $user->member_code ?? 3851;
    }
    private function getTradeType($memberCode, $defaultType, $alternativeType) {
        // member_codeが代理店(3851)かどうかの判別
        return $memberCode == 3851 ? $defaultType : $alternativeType;
    }

    /**
     * 個別取引の詳細ページから、取引詳細（[name, product_id, amount]の一覧）を取得、配列として返す
     * 
     * @param $crawler DomCrawlerで解析されたHTMLデータ
     * @param string $type 取引種別['sales', 'in', 'out']のどれか
     */
    public function getTradeData($crawler, $type) {
        // tbody要素の内容を取得
        $details = [];
        $crawler->filter('tbody.table-hover')->first()->filter('tr')
        ->each(function ($node) use (&$details, $type) {
            if (!($node->filter('th')->eq(0)->count() > 0)) {
                // 商品名の取得
                $row['name'] = $node->filter('td')->eq(0)->text();
              
                // 商品名から商品IDを取得
                $row['product_id'] = $this->getProductId($row['name']);

                // セット数を取得
                // 取引種別ごとにセット数が記入された列が異なるためmatch式で場合分け
                $amount = match ($type) {
                    'sales' => $node->filter('td')->eq(1)->text(),
                    'in' => $node->filter('td')->eq(2)->text(),
                    'out' => $node->filter('td')->eq(3)->text(),
                    default => null,
                };
                // セット数の値が存在しない場合(取引に２種類以上の取引種別が含まれる場合に発生)はその行はスキップ
                if ($amount) {
                    $row['amount'] = $amount;
                    $details[] = $row;
                }
            }
        });
        return $details;
    }
    public function getProductId($name) {
        // nameからproduct_idを取得
        $product = Product::where('name', $name)->first();
        if ($product) {
            return $product->id;
        } else {
            // 新規の商品名の場合、その商品をproduct_idを500番台として仮登録
            // productsテーブルのidが500以上で最も大きいidを取得
            $maxId = Product::where('id', '>', 500)->max('id');
            // 新しいidを決定
            $newId = $maxId ? $maxId + 1 : 501;
            // 新しいProductレコードを作成
            $product = new Product();
            $product->id = $newId;
            $product->name = $name;
            $product->product_type = 5;
            $product->save();

            return $newId;
        }
    }
    



    /**
     * 取引の新規登録or更新を行う
     *
     * @param string $tradeId NULLなら新規登録
     * @param array $tradeData ['member_code', 'date', 'trade_type', 'amount', 'status']を持つ（status=1:手動登録,2:自動登録(要確認取引)）
     * @param array $details 取引詳細(各要素に['product_id', 'amount']が必須)
     * @param int $change_detail 新規登録か$detailsに変更がある場合に「１」を指定
     */
    public function update_trade($tradeId, $tradeData, $details, $change_detail) {
        if ($tradeId) {
            // 編集の時は編集前データを保持
            $oldTrading = Trading::find($tradeId);
            $oldDetails = TradeDetail::where('trade_id', $tradeId)->get();
            // 編集前データは保持したまま変更用にコピー
            $trading = clone $oldTrading;
        } else {
            // 新規登録の場合
            $trading = new Trading();
        }
        if (!$trading) {
            throw new Exception("保存先($trading)を取得できませんでした");
        }
        
        // 取引を新規登録or編集
        $trading->fill($tradeData);
        $trading->save();

        // 変更ありの時、取引詳細を新規登録or編集
        if ($change_detail == 1) {
            $totalAmount = 0;
            if ($tradeId) {
                // 既にある取引詳細を全削除
                TradeDetail::where('trade_id', $tradeId)->delete();
            }
            foreach ($details as $detail) {
                $totalAmount += $detail['amount'];
                $tradeDetail = new TradeDetail();
                $tradeDetail->trade_id = $trading->id;
                $tradeDetail->product_id = $detail['product_id'];
                $tradeDetail->amount = $detail['amount'];
                $tradeDetail->save();
            }

            // 合計セット数一致確認
            if ($totalAmount != $tradeData['amount']) {
                throw new Exception("「取引セット数」と「取引詳細の合計セット数」が一致しません");
            }
        }

        // 編集前の取引が存在する場合
        if ($tradeId) {
            // 取引ユーザーが変更された場合は変更前のユーザーの最新注文&年間実績&資格手当を更新
            if ($tradeData['member_code'] != $oldTrading->member_code) {
                $this->refresh($oldTrading->member_code);
            }
            // 預入れor預出しの時の処理
            if (in_array($oldTrading->trade_type, config('custom.depo_tradeTypes'))) {
                // 現在合計預けセット数＆DepoRealtimeテーブルを更新（削除）
                $this->saveDepoForMember($oldTrading->member_code, $oldTrading->trade_type, $oldTrading->amount, $oldDetails, -1);
            }
        }

        // 最新注文&年間実績&資格手当を更新
        $this->refresh($tradeData['member_code']);

        // 預入れor預出しの時の処理
        if (in_array($tradeData['trade_type'], config('custom.depo_tradeTypes'))) {
            // 現在合計預けセット数＆DepoRealtimeテーブルを更新（追加）
            $this->saveDepoForMember($tradeData['member_code'], $tradeData['trade_type'], $tradeData['amount'], $details, 1);
        }
    }

}
