<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Models\Trading;
use App\Models\TradeDetail;
use App\Http\Controllers\FunctionsController;
use Carbon\Carbon;
use DOMDocument;
use DOMXPath;

class HomeController extends Controller
{
    // FunctionsControllerのメソッドを$this->FunctionsControllerで呼び出せるようにする
    private $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->FunctionsController = $functionsController;
    }

    public function dashboard(){
        $user = Auth::user();
        if ($user->permission == 1) {
            return redirect()->route('sales');
        } else {
            $latest = Trading::orderBy('updated_at', 'DESC')
            ->select('updated_at')
            ->first();
            return view('dashboard', compact('user', 'latest'));
        }
    }
    public function show_dashboard(Request $request){
        $request->validate([
            'user_dashboard' => 'required|integer',
        ]);
        $member_code = $request->input('user_dashboard');
        $user = User::where('member_code', $member_code)->first();
        $latest = Trading::orderBy('updated_at', 'DESC')
            ->select('updated_at')
            ->first();
        return view('dashboard', compact('user', 'latest'));
    }
 
    public function upload() {
        return view('upload');
    }

    public function sales_home() {
        // depo_statusが0ではない行を取得
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code', 'sales', 'latest_trade', 'sub_leader', 'sub_number', 'sub_now')
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
        $latest = Trading::orderBy('updated_at', 'DESC')
            ->select('updated_at')
            ->first();
        return view('sales-home', compact('users', 'latestTrades', 'latest'));
    }

    public function sales_detail($member_code){
        $years = config('custom.sales_howManyYears');
        $data = $this->FunctionsController->get_sales_detail($member_code, $years);
        return view('sales-detail', compact('data'));
    }

    public function sales_list($member_code)
    {
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name')
            ->first();

        // 開始年と終了年を計算
        $years = config('custom.sales_howManyYears');
        $startDate = Carbon::now()->subYears($years - 1)->startOfYear();
        $endDate = Carbon::now()->endOfYear();
    
        $tradings = Trading::with(['tradeType' => function($query) {
                $query->select('trade_type', 'name');
            }])
            ->where('member_code', $member_code)
            ->whereBetween('date', [$startDate, $endDate])
            ->select('id', 'date', 'trade_type', 'amount')
            ->orderBy('date', 'DESC')
            ->get();
    
        // 年ごとに取引記録をグループ化
        $groupedTradings = $tradings->groupBy(function($date) {
            return Carbon::parse($date->date)->format('Y');
        });
    
        return view('sales-list', compact('user', 'groupedTradings'));
    }

    public function sales_trade($member_code, $trade_id) {
        $trade = Trading::with(['tradeType' => function($query) {
                $query->select('trade_type', 'name', 'caption');
            }])
            ->with(['user' => function($query) {
                $query->select('member_code', 'name');
            }])
            ->where('member_code', $member_code)
            ->where('id', $trade_id)
            ->select('id', 'member_code', 'date', 'trade_type', 'amount')
            ->first();
        
        abort_unless($trade, 404);

        $details = TradeDetail::with('product')
            ->where('trade_id', $trade_id)
            ->get();

        return view('sales-trade', compact('trade', 'details'));
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
        $data = $this->FunctionsController->get_depo_detail($member_code);
        return view('depo-detail', compact('data'));
    }

    public function depo_detail_history($member_code) {
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

        return view('depo-detail-history', compact('user', 'tradings', 'products', 'amountsSelected'));
    }

    public function sub_detail($member_code){
        $user = User::where('member_code', $member_code)
            ->select('member_code', 'name', 'sub_leader', 'sub_number', 'sub_now')
            ->first();
        $groupMembers = User::where('sub_number', $user->sub_leader)
            ->select('member_code', 'name')
            ->get();
        
        // 過去6ヵ月の傘下営業所の取引を取得
        $groupTradings = [];
        $currentDate = Carbon::now();
        $startDate = $currentDate->copy()->subMonths(config('custom.sub_monthsCovered'))->addDay();
        foreach ($groupMembers as $member) {
            $tradings = Trading::where('date', '>=', $startDate)
                ->where('member_code', $member->member_code)
                ->whereIn('trade_type', config('custom.sales_tradeTypesEigyosho'))
                ->select('id', 'member_code', 'date', 'trade_type', 'amount')
                ->orderBy('date', 'ASC')
                ->get();
            $groupTradings[] = $tradings;
        }
        
        return view('sub-detail', compact('user', 'groupMembers', 'groupTradings', 'currentDate'));
    }

    public function trade_detail($member_code, $trade_id) {
        $trade = Trading::with(['tradeType' => function($query) {
                $query->select('trade_type', 'name');
            }])
            ->with(['user' => function($query) {
                $query->select('member_code', 'name');
            }])
            ->where('member_code', $member_code)
            ->where('id', $trade_id)
            ->select('id', 'member_code', 'date', 'trade_type', 'amount')
            ->first();
        
        abort_unless($trade, 404);

        $details = $details = TradeDetail::with('product')
            ->where('trade_id', $trade_id)
            ->get();

        return view('trade-detail', compact('trade', 'details'));
    }

    public function admin(Request $request){
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code', 'sales', 'latest_trade', 'sub_leader', 'sub_now')
            ->orderBy('priority', 'ASC')
            ->get();

        $trades = Trading::with(['user' => function($query) {
                $query->select('member_code', 'name');
            }])
            ->orderBy('date', 'DESC')
            ->get();

        if ($request->isMethod('post')) {
            $request->validate([
                'trading' => 'required|integer',
            ]);
            $tradeId = $request->input('trading');
            $display = Trading::with(['user' => function($query) {
                    $query->select('member_code', 'name');
                }])
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

        $refreshLogs = $this->FunctionsController->getRefreshLog();

        return view('admin', compact('users', 'trades', 'display', 'details', 'refreshLogs'));
    }

    public function refresh_member(Request $request) {
        $member_code = $request->input('member_code');
        DB::beginTransaction();
        try {
            $this->FunctionsController->refresh($member_code);
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
            $this->FunctionsController->refresh($users);
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
            DB::table('users')->where('status', 1)
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

    public function test_(){
        // URLからHTMLを取得
        $url = 'https://www.data.jma.go.jp/stats/etrn/view/10min_s1.php?prec_no=49&block_no=47638&year=2023&month=12&day=01';

        // cURLを使用してデータを取得
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $html = curl_exec($ch);
        curl_close($ch);
dd($html);
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        // id="tablefix1"のtableを取得
        $tables = $xpath->query('//table[@id="tablefix1"]');

        $arr = array();

        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');
            foreach ($rows as $row) {
                $cols = $row->getElementsByTagName('td');
                if ($cols->length > 0) {
                    // 'temperature', 'humidity', 'sunlight'の値が数値の文字列の場合は数値に変換、それ以外の場合はnullを格納
                    $temperature = is_numeric($cols->item(4)->nodeValue) ? floatval($cols->item(4)->nodeValue) : null;
                    $humidity = is_numeric($cols->item(5)->nodeValue) ? floatval($cols->item(5)->nodeValue) : null;
                    $sunlight = is_numeric($cols->item(10)->nodeValue) ? intval($cols->item(10)->nodeValue) * 10 : null;
                    // 0, 4, 5, 10番目の列のデータを取得
                    $arr[] = array(
                        'time' => $cols->item(0)->nodeValue,
                        'temperature' => $temperature,
                        'humidity' => $humidity,
                        'sunlight' => $sunlight
                    );
                }
            }
        }
// dd($arr);
        // データをビューに渡す
        return view('test', ['weatherData' => $arr]);
    }


    public function test()
    {
        // ログイン情報
        // $loginUrl = 'https://looop-denki.com/mypage/auth/login/';
        $loginUrl = 'https://www.mikigroup.jp/login';
        $dairiten_cd = '3851';
        $password = 'vs6ky99j';
        $token = 'qAuMcDUoeRZj0lb2OQ8Gw728yolX8oaYgs5DSOAf';
    
        // 初期化
        // $ch = curl_init();
        // curl_setopt($ch, CURLOPT_URL, $loginUrl);
        // curl_setopt($ch, CURLOPT_POST, 1);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        //     'dairiten_cd' => $dairiten_cd,
        //     'password' => $password,
        //     '_token' => $token
        // ]));
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_COOKIEJAR, storage_path('app/cookie.txt'));
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, [
        //     'User-Agent: Mozilla/5.0',
        //     'Accept: text/html',
        //     'Referer: https://www.mikigroup.jp/login', // リファラーを追加
        // ]);
    
        // $response = curl_exec($ch);
    
        // ログイン後のページからデータを取得
        // $dataUrl = 'https://www.mikigroup.jp/month02?jutyuno=60376&dendt=20241025';
        // $dataUrl = 'https://www.mikigroup.jp/jisseki02';
        $dataUrl = 'https://www.mikigroup.jp/month01';
        
        $tables = $this->FunctionsController->get_tables_url($dataUrl);

        $firstTable = $tables->item(0);
        // tableの行を取得
        $rows = $firstTable->getElementsByTagName('tr');
        // 行ごとにデータを取得
        $tradeList = [];
        foreach ($rows as $i => $row) {
            if ($i < 5) continue; // 先頭の5行をスキップ
        
            $cols = $row->getElementsByTagName('td');
            $rowData = [];
            foreach ($cols as $j => $col) {
                if ($j == 0) {
                    // aタグのhref属性を取得
                    $link = $col->getElementsByTagName('a');
                    if ($link->length > 0) {
                        $rowData[] = $link->item(0)->getAttribute('href');
                    }
                } elseif ($j > 1) {
                    $rowData[] = trim($col->nodeValue);
                }
            }
            $tradeList[] = $rowData;
        }  
    // dd($tradeList);
    
        $details = [];
        foreach ($tradeList as $index => $trade) {
            $tradeUrl = "https://www.mikigroup.jp/" . $trade[0];
            $tables = $this->FunctionsController->get_tables_url($tradeUrl);
            $detail = [];
            for ($k = 0; $k < count($tables); $k++) {
                if ($k < 1) continue;
                $firstTable = $tables->item($k);
                // tableの行を取得
                $rows = $firstTable->getElementsByTagName('tr');
                // 行ごとにデータを取得
                $tableData = [];
                foreach ($rows as $i => $row) {
                    $cols = $row->getElementsByTagName('td');
                    $rowData = [];
                    foreach ($cols as $col) {
                        $rowData[] = trim($col->nodeValue);
                    }
                    $tableData[] = $rowData;
                }
                $detail[] = $tableData;
            }
            $details[] = $detail;
        }
    dd($tradeList, $details);
        
        $tradeType = TradeType::all();
        // データをビューに渡す
        return view('test', compact('tradeList', 'details', 'tradeType'));
    }

}
