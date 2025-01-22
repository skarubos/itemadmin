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

    /*********************  ここからリフレッシュ関連  ******************************/

    /**
     * 【特定のユーザーのみ】年間実績 & 最新注文 & 資格手当を更新
     *
     * @param int $member_code
     */
    public function refreshMember($member_code)
    {
        $user = User::where('member_code', $member_code)->first();
        // 年間実績
        $sales = Trading::getSalesForMember($member_code);
        $user->sales = $sales;
        // 最新注文
        $latest = Trading::getLatestForMember($member_code);
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
    }

    /**
     * 【全ユーザー】年間実績 & 最新注文 & 資格手当を更新
     *
     * @param Collection $users
     */
    public function refreshAll($users)
    {
        $this->refresh_sales($users);
        $this->refresh_latest($users);
        $this->refresh_sub($users);
        DepoRealtime::refreshDepoRealtime();
    }

    // 全ユーザーの実績を更新
    public function refresh_sales($users) {
        foreach ($users as $user) {
            $sales = Trading::getSalesForMember($user->member_code);
            $user->sales = $sales;
            $user->save();
        }
    }
    // 全ユーザーの最新取引を更新
    public function refresh_latest($users) {
        foreach ($users as $user) {
            $latest = Trading::getLatestForMember($user->member_code);
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
     * 任意のサブリーダーの資格手当を取得
     *
     * @param string $subLeaderValue グループナンバー
     * @param mixed $startDate 資格手当の対象となる最初の日付
     * @param array $tradeTypes 実績対象の取引タイプの配列
     * @param int $subMinSet 資格手当対象となる最小合計セット数
     * @return int 資格手当対象となる傘下営業所の人数
     */
    private function getSubForMember($subLeaderValue, $startDate, $tradeTypes, $subMinSet) {
        $num = User::where('sub_number', $subLeaderValue)
                ->whereHas('tradings', function ($query) use ($startDate, $tradeTypes, $subMinSet) {
                    $query->where('date', '>=', $startDate)
                        ->whereIn('trade_type', $tradeTypes)
                        ->havingRaw('SUM(amount) > ?', [$subMinSet]);
                })
                ->count();
        return $num;
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





    /*********************  ここからスクレイピング関連  ******************************/


    /**
     * 「月間取引一覧」ページから未登録の商品取引の一覧を取得、配列として返す
     * 
     * @param $crawler DomCrawlerで解析されたHTMLデータ
     * @param int $month 参照すべき年月(取引が未登録かどうかの判定に使用)
     */
    public function getNewTrade($crawler, $month)
    {
        // キー名の配列（取引セット数の3列['実績', '入庫', '出庫']に対応する）
        $type = ['sales', 'in', 'out'];

        // tbody要素から取引一覧を取得
        $tradeData = [];
        $crawler->filter('tbody.table-hover tr')->each(function ($node) use (&$tradeData, $type) {
            $tradeData[] = [
                'link' => $node->filter('td')->eq(1)->filter('a')->attr('href'),
                'date' => $node->filter('td')->eq(3)->text(),
                'name' => $node->filter('td')->eq(6)->text(),
                $type[0] => $node->filter('td')->eq(7)->text(),
                $type[1] => $node->filter('td')->eq(8)->text(),
                $type[2] => $node->filter('td')->eq(9)->text(),
            ];
        });

        // sales, in, outの3つ全てに値が存在しない要素をフィルタリング
        $tradeData = array_filter($tradeData, function($row) use ($type) {
            return !empty($row[$type[0]]) || !empty($row[$type[1]]) || !empty($row[$type[2]]);
        });
    
        // 指定された月の全取引のcheck_noを配列として取得
        $arr = $this->getJutyunoArr($month);
    
        // 未登録の取引を抜粋、必要な属性を設定して新しい配列に入れ替える
        $newTrade = [];
        foreach ($tradeData as $trade) {
            // 'link'から'jutyuno'を抽出して追加
            $trade['jutyuno'] = $this->getNo($trade['link']);
            // 既に登録されている取引をスキップ
            if (in_array($trade['jutyuno'], $arr)) {
                continue;
            }
            if ($trade[$type[0]]) {
                $newTrade[] = $this->setTradeAttributes($trade, $type[0]);
            }
            if ($trade[$type[1]]) {
                $newTrade[] = $this->setTradeAttributes($trade, $type[1]);
            }
            if ($trade[$type[2]]) {
                $newTrade[] = $this->setTradeAttributes($trade, $type[2]);
            }
        }
    
        // NULLを除外して結果を返す
        return array_filter($newTrade);
    }
    private function getJutyunoArr($month)
    {
        // 'YYYYMM'から'YYYY-MM'形式に変換
        $formattedMonth = substr($month, 0, 4) . '-' . substr($month, 4, 2);
    
        $arr = Trading::whereNotNull('check_no')
            ->where('date', 'like', $formattedMonth . '%') // 指定された月で絞り込み
            ->pluck('check_no')
            ->toArray();
    
        return $arr;
    }
    private function getNo($string)
    {
        $string = substr($string, strlen(config('secure.url_forRemove')));
        $string = substr($string, 0, -15);
        return $string;
    }
    private function setTradeAttributes($trade, $type)
    {
        // 氏名からmember_codeを取得
        $memberCode = $this->getMemberCode($trade['name']);
        // member_codeと列情報$typeから取引種別を取得
        $tradeType = $this->selectTradeType($memberCode, $type);

        // 取引の属性を設定
        $trade = [
            'check_no' => $trade['jutyuno'],
            'link' => $trade['link'],
            'date' => $trade['date'],
            'member_code' => $memberCode,
            'type' => $type,
            'amount' => $trade[$type],
            'trade_type' => $tradeType,
        ];

        return $trade;
    }    
    private function getMemberCode($name)
    {
        $dairiten = 3851;
        if ($name == '店頭販売') {
            return $dairiten;
        }
        // ユーザーを検索し、見つからない場合は代理店のmember_codeを返す
        $user = User::where('name', $name)->first();
        return $user->member_code ?? $dairiten;
    }
    public function selectTradeType($memberCode, $type)
    {
        $types = [
            'sales' => [110, 10],
            'in' => [111, 11],
            'out' => [121, 21],
        ];
        return $memberCode == 3851 ? $types[$type][0] : $types[$type][1];
    }

    /**
     * 個別取引の詳細ページから、取引詳細（[name, product_id, amount]の一覧）を取得、配列として返す
     * 
     * @param $crawler DomCrawlerで解析されたHTMLデータ
     * @param string $type 取引種別['sales', 'in', 'out']のどれか
     */
    public function getTradeData($crawler, $type)
    {
        // tbody要素の内容を取得
        $details = [];
        $crawler->filter('tbody.table-hover')->first()->filter('tr')
        ->each(function ($node) use (&$details, $type) {
            if (!($node->filter('th')->eq(0)->count() > 0)) {
                // 商品名の取得
                $detail['name'] = $node->filter('td')->eq(0)->text();
              
                // 商品名から商品IDを取得
                $detail['product_id'] = Product::getProductId($detail['name']);

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
                    $detail['amount'] = $amount;
                    $details[] = $detail;
                }
            }
        });
        return $details;
    }

    
    /**
     * 取引の新規登録or更新を行う
     *
     * @param string $tradeId NULLなら新規登録
     * @param array $tradeData ['member_code', 'date', 'trade_type', 'amount', 'status']を持つ（status=1:手動登録,2:自動登録(要確認取引)）
     * @param array $details 取引詳細(各要素に['product_id', 'amount']が必須)
     * @param int $change_detail 新規登録か$detailsに変更がある場合に「１」を指定
     */
    public function update_trade($tradeId, $tradeData, $details, $change_detail)
    {
        if ($tradeId) {
            // 編集の時は編集前データを保持
            $oldTrading = Trading::find($tradeId);
            $oldDetails = TradeDetail::where('trade_id', $tradeId)->get();
            // 編集前データは保持したまま変更用にコピー
            $trading = clone $oldTrading;
            if ($trading->trade_type != $tradeData['trade_type'] && is_null($details)) {
                throw new Exception("詳細のない取引は取引種別を変更できません");
            }
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
                $this->refreshMember($oldTrading->member_code);
            }
            // 預入れor預出しの時の処理
            if (in_array($oldTrading->trade_type, config('custom.depo_tradeTypes'))) {
                // 現在合計預けセット数＆DepoRealtimeテーブルを更新（削除）
                $this->saveDepoForMember($oldTrading->member_code, $oldTrading->trade_type, $oldTrading->amount, $oldDetails, -1);
            }
        }

        // 最新注文&年間実績&資格手当を更新
        $this->refreshMember($tradeData['member_code']);

        // 預入れor預出しの時の処理
        if (in_array($tradeData['trade_type'], config('custom.depo_tradeTypes'))) {
            // 現在合計預けセット数＆DepoRealtimeテーブルを更新（追加）
            $this->saveDepoForMember($tradeData['member_code'], $tradeData['trade_type'], $tradeData['amount'], $details, 1);
        }
    }
    
    /**
     * 任意のユーザーの預け記録を更新
     * 現在合計預けセット数＆DepoRealtimeテーブルを更新
     *
     * @param string $memberCode
     * @param int $tradeType 
     * @param int $amount 取引合計セット数
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
            
            // amountに値を加算
            $depoRealtime->amount += $detail['amount'] * $sign;

            // 加算後の値が0である場合、物理削除
            if ($depoRealtime->amount == 0) {
                $depoRealtime->delete();
            } else {
                // それ以外の場合、保存
                $depoRealtime->save();
            }
        }
    }






    /*********************  ここからユーティリティ  ******************************/


    /**
     * 最終更新日を取得
     *
     * @return string 最終更新日('Y年n月j日') または '更新なし'
     */
    public function getLastUpdateDate()
    {
        $trade = Trading::getLatestTrade();
        $log = RefreshLog::getLastUpdate('scrape', true);

        // 両方が null の場合
        if (is_null($trade) && is_null($log)) {
            return "更新なし";
        }

        // 片方が null の場合の処理
        if (is_null($trade)) {
            $date = Carbon::parse($log->created_at);
        } elseif (is_null($log)) {
            $date = Carbon::parse($trade->date);
        } else {
            // 両方が取得できた場合
            $tradeDate = Carbon::parse($trade->date);
            $logDate = Carbon::parse($log->created_at);
            $date = $tradeDate->greaterThan($logDate) ? $tradeDate : $logDate;
        }

        return $date->format('Y年n月j日');
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
}
