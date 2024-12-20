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
use DOMDocument;
use DOMXPath;

class FunctionsController extends Controller
{
    // 任意のユーザーの年間実績を集計
    public function yearlySales($member_code) {
        $startOfYear = Carbon::now()->startOfYear();
        $sales = Trading::where('member_code', $member_code)
        ->where('date', '>=', $startOfYear)
        ->whereIn('trade_type', config('custom.sales_tradeTypes'))
        ->sum('amount');

        return $sales;
    }
    // 任意のユーザーの最新の注文を検索
    public function latestTrade($member_code) {
        // 移動(取引タイプ20)は２０セット以上のみ最新の取引として取得
        $tradeTypes = array_diff(config('custom.sales_tradeTypes'), [20]);
        $latest = Trading::where('member_code', $member_code)
            ->where(function($query) use ($tradeTypes) {
                $query->whereIn('trade_type', $tradeTypes)
                    ->orWhere(function($query) {
                        $query->where('trade_type', 20)
                                ->where('amount', '>=', 20);
                    });
            })
            ->orderBy('date', 'DESC')
            ->select('id')
            ->first();
    
        return $latest;
    }
    
    
    // 資格手当（過去6ヵ月の実績が昇級条件を満たすグループメンバーの人数）を更新
    public function subRefresh($users) {
        // sub_leaderの値が0でないユーザーを取得
        $usersWithSubLeader = $users->filter(function ($user) {
            return $user->sub_leader !== 0;
        });
        $currentDate = Carbon::now();
        $startDate = $currentDate->copy()->subMonths(config('custom.sub_monthsCovered'))->addDay();
        
        // $subs配列にsub_leaderの値を格納
        $subs = $usersWithSubLeader->pluck('sub_leader')->toArray();
        foreach ($usersWithSubLeader as $user) {
            $subLeaderValue = $user->sub_leader;
            // 過去6ヶ月の実績を持つユーザーの数を取得
            $nums = User::where('sub_number', $subLeaderValue)
                ->whereHas('tradings', function ($query) use ($startDate) {
                    $query->where('date', '>=', $startDate)
                        ->whereIn('trade_type', config('custom.sales_tradeTypesEigyosho'))
                        ->havingRaw('SUM(amount) > ?', [config('custom.sub_minSet')]);
                })
                ->count();
    
            // sub_nowカラムを更新、上限を5に設定
            $user->sub_now = min($nums*100, 500);
            $user->save();
        }
    }
    

    public function get_depo_detail($member_code){
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name', 'depo_status')
            ->first();
        $details = DepoRealtime::with('product')
            ->where('member_code', $member_code)
            ->where('amount', '!=', 0)
            ->orderBy('product_id', 'ASC')
            ->get();
        return [
            'user' => $user,
            'details' => $details,
        ];
    }

    public function get_sales_detail($member_code){
        $user = User::where('member_code', $member_code)
            ->select('name', 'member_code', 'sales', 'depo_status')
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
                ->whereIn('trade_type', config('custom.sales_tradeTypes'))
                ->sum('amount');
            $details[] = $monthlySales;
        }
        
        return [
            'user' => $user,
            'details' => $details,
        ];
    }

    public function get_tables_url($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_COOKIEFILE, storage_path('app/cookie.txt'));
        $data = curl_exec($ch);
        curl_close($ch);

        // DOMパーサーを使用してデータを解析
        $dom = new DOMDocument();
        @$dom->loadHTML($data);

        // table要素を取得
        $tables = $dom->getElementsByTagName('table');
        return $tables;
    }

}