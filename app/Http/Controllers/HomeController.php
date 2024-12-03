<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\DepoRealtime;
use App\Models\Trading;
use Carbon\Carbon;

class HomeController extends Controller
{

    public function refresh_sales() {
        // 【最新注文&年間実績（年初からの累計セット数）を更新】
        
        $startOfYear = Carbon::now()->startOfYear();
        $users = User::all();

        foreach ($users as $user) {
            // 最新注文
            $latest = Trading::where('member_code', $user->member_code)
                        ->whereIn('trading_type', [10, 11, 12, 20, 110, 111])
                        ->orderBy('date', 'DESC')
                        ->select('id')
                        ->first();
            $user->latest_trade = $latest ? $latest->id : null;
            
            // 年間実績
            $sales = Trading::where('member_code', $user->member_code)
                        ->where('date', '>=', $startOfYear)
                        ->whereIn('trading_type', [10, 11, 12, 20, 110, 111])
                        ->sum('amount');
            $user->sales = $sales;
            $user->save();
        }

        // 【資格手当（過去6ヵ月に実績のあるグループメンバーの人数）を更新】
        // sub_leaderの値が0でないユーザーを取得
        $usersWithSubLeader = User::where('sub_leader', '!=', 0)->get();
        $currentDate = Carbon::now(); $sixMonthsAgo = $currentDate->subMonths(6);
        // $subs配列にsub_leaderの値を格納
        $subs = $usersWithSubLeader->pluck('sub_leader')->toArray();
        foreach ($usersWithSubLeader as $user) {
            $subLeaderValue = $user->sub_leader;
            // 過去6ヶ月の実績を持つユーザーの数を取得
            $nums = User::where('sub_number', $subLeaderValue)
                ->whereHas('tradings', function ($query) use ($sixMonthsAgo) {
                    $query->where('date', '>=', $sixMonthsAgo)
                        ->whereIn('trading_type', [10, 11, 12]);
                }) ->count();

            // sub_nowカラムを更新、上限を5に設定
            $user->sub_now = min($nums, 5);
            $user->save();
        }

        return $this->sales_home();
    }
    
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
                ->whereIn('trading_type', [10, 11, 12, 110, 111])
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
            ->get();
        
        return view('depo-detail', compact('user', 'details'));
    }
}
