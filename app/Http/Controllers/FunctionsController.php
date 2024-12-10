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
use Carbon\Carbon;

class FunctionsController extends Controller
{
    // 任意のユーザーの年間実績を集計
    public function yearlySales($member_code) {
        $startOfYear = Carbon::now()->startOfYear();
        $sales = Trading::where('member_code', $member_code)
        ->where('date', '>=', $startOfYear)
        ->whereIn('trade_type', [10, 11, 12, 20, 110, 111])
        ->sum('amount');
        return $sales;
    }
    // 任意のユーザーの最新の注文を検索
    public function latestTrade($member_code) {
        $latest = Trading::where('member_code', $member_code)
        ->whereIn('trade_type', [10, 11, 12, 20, 110, 111])
        ->orderBy('date', 'DESC')
        ->select('id')
        ->first();
        return $latest;
    }
    // 資格手当（過去6ヵ月に実績のあるグループメンバーの人数）を更新
    public function subRefresh($users) {
        // sub_leaderの値が0でないユーザーを取得
        $usersWithSubLeader = $users->filter(function ($user) {
            return $user->sub_leader !== 0;
        });
        $currentDate = Carbon::now(); $sixMonthsAgo = $currentDate->subMonths(6);
        // $subs配列にsub_leaderの値を格納
        $subs = $usersWithSubLeader->pluck('sub_leader')->toArray();
        foreach ($usersWithSubLeader as $user) {
            $subLeaderValue = $user->sub_leader;
            // 過去6ヶ月の実績を持つユーザーの数を取得
            $nums = User::where('sub_number', $subLeaderValue)
                ->whereHas('tradings', function ($query) use ($sixMonthsAgo) {
                    $query->where('date', '>=', $sixMonthsAgo)
                        ->whereIn('trade_type', [10, 11, 12]);
                }) ->count();

            // sub_nowカラムを更新、上限を5に設定
            $user->sub_now = min($nums, 5);
            $user->save();
        }
    }

}