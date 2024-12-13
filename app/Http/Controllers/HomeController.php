<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Models\Trading;
use App\Models\TradeDetail;
use App\Http\Controllers\FunctionsController;
use Carbon\Carbon;

class HomeController extends Controller
{
    // FunctionsControllerのメソッドを$this->functionsControllerで呼び出せるようにする
    private $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functionsController = $functionsController;
    }

    public function dashboard(){
        $user = Auth::user();
        if ($user->permission == 1) {
            return redirect()->route('sales_home');
        } else {
            $data = $this->functionsController->get_depo_detail($user->member_code);
            return view('dashboard', compact('data'));
        }
    }
 
    public function upload() {
        return view('upload');
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
        $data = $this->functionsController->get_sales_detail($member_code);
        return view('sales-detail', compact('data'));
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
        $data = $this->functionsController->get_depo_detail($member_code);
        return view('depo-detail', compact('data'));
    }

    public function depo_detail_history($member_code) {
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name', 'depo_status')
            ->first();
    
        $tradings = Trading::where('member_code', $member_code)
            ->whereIn('trade_type', [11, 21, 111, 121])
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

        return view('depo-detail-history', compact('user', 'tradings', 'products', 'amountsSelected'));
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
