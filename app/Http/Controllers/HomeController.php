<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\IdRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Models\Trading;
use App\Models\TradeDetail;
use App\Models\TradeType;
use App\Models\RefreshLog;
use App\Http\Controllers\MyService;
use App\Http\Controllers\FunctionsController;
use Carbon\Carbon;
use Exception;

class HomeController extends Controller
{
    // FunctionsControllerのメソッドを$this->functionsで呼び出せるようにする
    private $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functions = $functionsController;
    }




    public function show_member_depo_history($member_code) {
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name', 'depo_status')
            ->first();
    
        $tradings = Trading::where('member_code', $member_code)
            ->whereIn('trade_type', config('custom.depo_tradeTypes'))
            ->select('id', 'date', 'trade_type', 'amount')
            ->orderBy('date', 'ASC')
            ->get();
            
        $allProducts = Product::orderBy('id', 'ASC')->get(['id', 'name']);
        
        // 各商品の総数量を記録する配列
        $totalQuantities = array_fill(0, $allProducts->count(), 0);
        
        // 各取引ごとのデータ取得と数量の配列作成
        foreach ($tradings as $trading) {
            $details = TradeDetail::with(['product' => function($query) {
                    $query->select('id', 'name'); // productsテーブルからidとnameのみを取得（リレーションを利用）
                }])
                ->where('trade_id', $trading->id)
                ->select('trade_id', 'product_id', 'amount')
                ->orderBy('product_id', 'ASC')
                ->get();
        
            $transactionAmounts = array_fill(0, $allProducts->count(), 0); // 初期化
            foreach ($details as $detail) {
                $productIndex = $allProducts->search(function ($product) use ($detail) {
                    return $product->id === $detail->product_id;
                });
        
                if ($productIndex !== false) {
                    $transactionAmounts[$productIndex] = $detail->amount;
                    $totalQuantities[$productIndex] += $detail->amount;
                }
            }
            $amounts[] = $transactionAmounts;
        }

        // 現在の預け入れ在庫depo_realtimeも加える
        $realtime_details = DepoRealtime::with(['product' => function($query) {
            $query->select('id', 'name');
        }])
        ->where('member_code', $member_code)
        ->where('amount', '!=', 0)
        ->select('product_id', 'amount')
        ->orderBy('product_id', 'ASC')
        ->get();
        $transactionAmounts = array_fill(0, $allProducts->count(), 0); // 初期化
        foreach ($realtime_details as $detail) {
            $productIndex = $allProducts->search(function ($product) use ($detail) {
                return $product->id === $detail->product_id;
            });
    
            if ($productIndex !== false) {
                $transactionAmounts[$productIndex] = $detail->amount;
                $totalQuantities[$productIndex] += $detail->amount;
            }
        }
        $amounts[] = $transactionAmounts;
      
        // $totalQuantitiesを使って、すべての取引で数量が0の商品のインデックスを特定
        $products = [];
        $amountsSelected = [];
        foreach ($totalQuantities as $index => $quantity) {
            if ($quantity > 0) {
                $products[] = $allProducts[$index]->name;
        
                foreach ($amounts as $transactionAmounts) {
                    $amountsSelected[$index][] = $transactionAmounts[$index];
                }
            }
        }

        return view('member.depo-history', compact('user', 'tradings', 'products', 'amountsSelected'));
    }





    














    public function dashboard()
    {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('sales');
        }
        // 最終更新日を取得
        $lastUpdate = $this->functions->getLastUpdateDate();
        return view('member.dashboard', compact('user', 'lastUpdate'));
    }
    public function show_dashboard(IdRequest $request)
    {
        $user = User::where('member_code', $request->input('id'))->first();

        // 最終更新日を取得
        $lastUpdate = $this->functions->getLastUpdateDate();
        return view('member.dashboard', compact('user', 'lastUpdate'));
    }

    public function show_trade($member_code, $trade_id)
    {
        $trade = Trading::getTrade($trade_id, $member_code);
        abort_unless($trade, 404);

        $details = TradeDetail::getTradeDetail($trade_id);

        return view('trading.detail', compact('trade', 'details'));
    }

    public function show_sales_home()
    {
        // ユーザーの一覧を取得
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code', 'sales', 'latest_trade', 'sub_leader', 'sub_number', 'sub_now')
            ->orderBy('priority', 'ASC')
            ->get();

        // latest_tradeに基づいて各ユーザーの最新取引を取得
        $latestTrades = [];
        foreach ($users as $user) {
            if ($user->latest_trade) {
                $latestTrades[$user->id] = Trading::getTrade($user->latest_trade);
                Carbon::parse($latestTrades[$user->id]->date)->format('y/n/j');
            }
        }

        // 最終更新日を取得
        $lastUpdate = $this->functions->getLastUpdateDate();
        
        // 自動登録された項目が存在するか確認
        $newTrade = Trading::where('status', 2)->count();
        $newProduct = Product::where('product_type', 5)->count();

        return view('sales-home', compact('users', 'latestTrades', 'lastUpdate' ,'newTrade', 'newProduct'));
    }

    public function show_member_sales($member_code){
        // 表示する年数を取得
        $years = config('custom.sales_howManyYears');
        // 年数分の実績データを取得
        $data = $this->functions->get_sales_detail($member_code, $years);
        return view('member.sales', compact('data'));
    }

    public function show_member_sales_list($member_code)
    {
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name')
            ->first();

        // 開始年と終了年を計算
        $years = config('custom.sales_howManyYears');
        $startDate = Carbon::now()->subYears($years - 1)->startOfYear();
        $endDate = Carbon::now()->endOfYear();
    
        // 取引を取得
        $tradings = Trading::getTradings($member_code, $startDate, $endDate);
    
        // 年ごとに取引記録をグループ化
        $groupedTradings = $tradings->groupBy(function($date) {
            return Carbon::parse($date->date)->format('Y');
        });
    
        return view('member.sales-list', compact('user', 'groupedTradings'));
    }

    public function show_depo_home() {
        // depo_statusが0ではない行を取得
        $items = User::where('depo_status', '!=', 0)
            ->select('member_code', 'name', 'depo_status')
            ->orderBy('depo_status', 'DESC')
            ->get();
        $sumDepoStatus = $items->sum('depo_status');

        return view('depo-home', compact('items', 'sumDepoStatus'));
    }
    
    public function show_member_depo($member_code){
        $data = $this->functions->getMemberDepo($member_code);
        return view('member.depo', compact('data'));
    }
    
    public function show_member_sub($member_code){
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name', 'sub_leader', 'sub_number', 'sub_now')
            ->first();
        // 傘下営業所を取得
        $groupMembers = User::where('sub_number', $user->sub_leader)
            ->select('member_code', 'name')
            ->get();
        
        // 傘下営業所の取引を取得（資格手当の対象となる期間のみ）
        $groupTradings = [];
        $currentDate = Carbon::now();
        $startDate = $currentDate->copy()->subMonths(config('custom.sub_monthsCovered'))->addDay();
        foreach ($groupMembers as $member) {
            $tradings = Trading::getTradings($member->member_code, $startDate, $currentDate)
                ->whereIn('trade_type', config('custom.sales_tradeTypesEigyosho'));
                
            $groupTradings[] = $tradings;
        }
        
        return view('member.sub', compact('user', 'groupMembers', 'groupTradings', 'currentDate'));
    }

    public function show_upload() {
        return view('upload');
    }

    public function show_admin(Request $request)
    {
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code', 'sub_leader', 'sub_now')
            ->orderBy('priority', 'ASC')
            ->get();

        $trades = Trading::getTradings(null, null, null, 'id');

        $monthArr = $this->functions->getMonthArr(2);

        // 自動更新の最新ログを取得
        $methods = ['scrape', 'refresh_sub', 'refresh'];
        $refreshLogs = RefreshLog::getLastUpdate($methods);

        return view('admin', compact('users', 'trades', 'monthArr', 'refreshLogs'));
    }

    public function show_setting() {
        // 「設定」タブ表示に必要な要素を全て配列に格納
        $items = [
            [
                'label' => '取引種別',
                'key' => ['trade_type', 'name'],
                'route' => ['tradeType.create', 'tradeType.edit']
            ],
            [
                'label' => '商品',
                'key' => ['product_type', 'name'],
                'route' => ['product.create', 'product.edit']
            ],
            [
                'label' => 'メンバー',
                'key' => ['member_code', 'name'],
                'route' => ['user.create', 'user.edit']
            ],
        ];

        // 選択リストへ表示する内容
        $selects = [];
        $selects[] = TradeType::orderBy('trade_type', 'ASC')->get();
        $selects[] = Product::get();
        $selects[] = User::orderBy('priority', 'ASC')->get();

        return view('setting', compact('items', 'selects'));
    }


    public function refresh_member(Request $request) {
        $member_code = $request->input('member_code');
        DB::beginTransaction();
        try {
            $this->functions->refresh($member_code);
            DB::commit();
            return redirect()->route('sales')
                ->with('success', $member_code . ':データの更新が正常に行われました。【最新注文&年間実績&資格手当】');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('全部更新(refresh_all)に失敗: ' . $e->getMessage());
            return back()->withErrors(['error' => 'データの更新処理に失敗しました。', 'エラーログ保存先：\storage\logs\laravel.log']);
        }
    }

    public function refresh_all() {
        $users = User::where('status', 1)->get();
        DB::beginTransaction();
        try {
            $this->functions->refresh($users);
            DB::commit();
            return redirect()->route('sales')
                ->with('success', 'データの更新が正常に行われました。【最新注文&年間実績&資格手当】');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('全部更新(refresh_all)に失敗: ' . $e->getMessage());
            return back()->withErrors(['error' => 'データの更新処理に失敗しました。', 'エラーログ保存先：\storage\logs\laravel.log']);
        }
    }

    public function reset_all() {
        DB::beginTransaction();
        try {
            User::where('status', 1)
                ->update([
                    'sales' => 0,
                    'latest_trade' => null,
                    'sub_now' => 0,
                ]);
            DB::commit();
            return redirect()->route('sales')
                ->with('success', '最新注文&年間実績&資格手当をリセットしました。');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('更新のリセット(reset_all)に失敗: ' . $e->getMessage());
            return back()->withErrors(['error' => '更新のリセット(reset_all)に失敗しました。', 'エラーログ保存先：\storage\logs\laravel.log']);
        }
    }
}
