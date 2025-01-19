<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\IdRequest;
use App\Models\User;
use App\Models\Trading;
use App\Models\TradeDetail;
use App\Models\TradeType;
use App\Http\Controllers\FunctionsController;
use Carbon\Carbon;

class TradingController extends Controller
{
    // FunctionsControllerのメソッドを$this->functionsで呼び出せるようにする
    private $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functions = $functionsController;
    }

    public function show_trade_check() {
        // 自動登録された取引を取得
        $params = [
            'sortColumn' => 'date',
            'sortDirection' => 'ASC',
            'status' => 2,
        ];
        $tradings = Trading::getTradings($params);

        return view('trading.check', compact('newTrade'));
    }

    public function change_status($tradeId, $remain) {
        try {
            $trade = Trading::find($tradeId);
            if (!$trade) {
                throw new \Exception('取引ID'.$tradeId.'が見つかりません。');
            }
            $trade->status = 1;
            $trade->save();

            // 残り件数に応じてリダイレクト先を設定
            return $this->handleRedirect($tradeId, $remain);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => '更新に失敗！'.$e]);
        }
    }
    /**
     * リダイレクト処理を行う
     *
     * @param string $tradeId エラー表示ID
     * @param int|null $remain 残り件数
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleRedirect($tradeId, $remain)
    {
        $remain--;
        if ($remain > 0) {
            return redirect()->route('trade.check')
                ->with('success', '取引ID【'.$tradeId.'】の確認完了！残り'.$remain.'件');
        } elseif ($remain == 0) {
            return redirect()->route('sales')
                ->with('success', '取引ID【'.$tradeId.'】の確認完了！');
        } else {
            return redirect()->route('sales')
                ->with('success', '取引ID【'.$tradeId.'】の確認完了！残り件数(remain)の値が不正です。');
        }
    }

    public function show_edit_trade_request(IdRequest $request) {
        // POSTされたIDをURLに含めてリダイレクト
        $route = '/trade/edit/' . $request->input('id') . '/0';
        return redirect($route);
    }
    /**
     * 取引の編集ページを表示（通常編集or自動登録取引の確認時の編集）
     *
     * @param int $tradeId 取引ID
     * @param int $remain 0:通常の編集、1~:自動登録取引の確認時の残り件数
     */
    public function show_edit_trade($tradeId, $remain)
    {
        $trade = Trading::getTrade($tradeId);
        $details = TradeDetail::getTradeDetail($tradeId);

        // 新規登録時と形式を合わせるため、各要素にproductのnameを設定
        $details->each(function ($tradeDetail) {
            $tradeDetail->name = $tradeDetail->product->name;
        });

        // ドロップダウンリスト表示に必要なデータを取得
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code')
            ->orderBy('priority', 'ASC')
            ->get();
        $trade_types = $remain ? TradeType::getTradeTypes('tradeTypes_for_checkTrade') : TradeType::getTradeTypes();
        
        return view('trading.edit', compact('trade', 'details', 'users', 'trade_types', 'remain'));
    }

    public function delete(IdRequest $request)
    {
        // 取引IDから取引内容を取得
        $tradeId = $request->input('id');
        $trading = Trading::find($tradeId);
        if (!$trading) {
            return back()->withErrors(['error' => '指定された取引(ID:'.$tradeId.')はデータベースに存在しません。']);
        } else {
            $tradeType = $trading->trade_type;
            $memberCode = $trading->member_code;
            $amount = $trading->amount;
            $details = TradeDetail::getTradeDetail($tradeId);
        }

        DB::beginTransaction();
        try {
            // 外部キー制約無効化のためusersテーブルの関連レコードを更新
            User::where('latest_trade', $tradeId)->update(['latest_trade' => null]);

            // trade_detailsの該当カラムを削除
            TradeDetail::where('trade_id', $tradeId)->delete();

            // tradingsの該当カラムを削除
            $trading->delete();

            // 注文の時の処理
            if (in_array($tradeType, config('custom.sales_tradeTypes'))) {
                // 最新注文&年間実績&資格手当を更新
                $this->functions->refresh($memberCode);
            }

            // 預入れor預出しの時の処理
            if (in_array($tradeType, config('custom.depo_tradeTypes'))) {
                // 現在合計預けセット数＆DepoRealtimeテーブルを更新
                $this->functions->saveDepoForMember($memberCode, $tradeType, $amount, $details, -1);
            }

            DB::commit();

            return redirect()->route('admin')->with('success', '取引をデータベースから正常に削除しました');
        } catch (\Exception $e) {
            // トランザクションロールバック
            DB::rollBack();
            \Log::error('Data update(delete) failed: ' . $e->getMessage());
            return back()->withErrors(['error' => '取引の削除中にエラーが発生しました: ' . $e->getMessage()]);
        }
    }
}
