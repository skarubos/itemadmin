<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trading;
use Carbon\Carbon;

class TradingController extends Controller
{
    //
    public function show_trade_check() {
        // 自動登録された取引を取得
        $newTrade = Trading::getTradings(null, null, null, 'date', 'ASC', 2);
        return view('trade-check', compact('newTrade'));
    }

    public function change_status($tradeId, $remain) {
        try {
            $trade = Trading::find($tradeId);
            if (!$trade) {
                throw new \Exception('取引ID'.$tradeId.'が見つかりません。');
            }
            $trade->status = 1;
            $trade->save();
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
        } catch (\Exception $e) {
            return back()->withErrors(['error' => '更新に失敗！'.$e]);
        }
    }
}
