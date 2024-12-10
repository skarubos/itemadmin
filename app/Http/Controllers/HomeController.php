<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Models\Trading;
use App\Models\TradeDetail;
use Carbon\Carbon;

class HomeController extends Controller
{
   
    public function sales_home() {
        // depo_statusが0ではない行を取得
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code', 'sales', 'latest_trade', 'sub_leader', 'sub_now')
            ->orderBy('priority', 'ASC')
            ->get();
        // latest_tradeに基づいて各ユーザーの最新取引を取得
        $latestTrades = [];
        foreach ($users as $user) {
            if ($user->latest_trade) {
                $latest = Trading::where('id', $user->latest_trade)
                    ->select('id', 'date', 'amount')
                    ->first();
                if ($latest) {
                    $latestTrades[$user->id] = $latest;
                }
            }
        }
        return view('sales-home', compact('users', 'latestTrades'));
    }

    public function sales_detail($member_code){
        $user = User::where('member_code', $member_code)
            ->select('name', 'member_code', 'phone_number', 'sales', 'depo_status', 'sub_now')
            ->first();

        // 1月初めから現在月までの開始日と終了日を取得
        $startOfYear = Carbon::now()->startOfYear();
        $currentMonth = Carbon::now()->month;
        $currentDate = Carbon::now();
        // 合計実績の計算
        $details = [];
        for ($month = 1; $month <= $currentMonth; $month++) {
            $startOfMonth = Carbon::createFromDate(Carbon::now()->year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::createFromDate(Carbon::now()->year, $month, 1)->endOfMonth();
            $monthlySales = Trading::where('member_code', $member_code)
                ->whereBetween('date', [$startOfMonth, $endOfMonth])
                ->whereIn('trade_type', [10, 11, 12, 110, 111])
                ->sum('amount');
            $details[] = $monthlySales;
        }
        
        return view('sales-detail', compact('user', 'details'));
    }

    public function depo_home() {
        // depo_statusが0ではない行を取得
        $items = User::where('depo_status', '!=', 0)
            ->select('member_code', 'name', 'depo_status')
            ->orderBy('depo_status', 'DESC')
            ->get();

        return view('depo-home', compact('items'));
    }
    
    public function depo_detail($member_code){
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name', 'depo_status')
            ->first();
        $details = DepoRealtime::with('product')
            ->where('member_code', $member_code)
            ->where('amount', '!=', 0)
            ->orderBy('product_id', 'ASC')
            ->get();
        
        return view('depo-detail', compact('user', 'details'));
    }

    public function admin(Request $request){
        $trades = Trading::with('user')
            ->orderBy('updated_at', 'DESC')
            ->get();

        if ($request->isMethod('post')) {
            $tradeId = $request->input('trading');
            $display = Trading::with('user')
                ->where('id', $tradeId)
                ->select('member_code', 'amount')
                ->first();
            $details = TradeDetail::with('product')
                ->where('trade_id', $tradeId)
                ->get();
        } else {
            $details = null;
            $display = null;
        }

        return view('admin', compact('trades', 'display', 'details'));
    }

}
