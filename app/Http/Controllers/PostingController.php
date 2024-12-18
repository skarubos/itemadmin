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
use App\Http\Controllers\FunctionsController;
use Carbon\Carbon;

class PostingController extends Controller
{
    // FunctionsControllerのメソッドを$this->functionsControllerで呼び出せるようにする
    private $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functionsController = $functionsController;
    }
    
    public function upload_check(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        // $summarysに取引データの各項目を検索＆取得して格納
        $summarys = [
            'no' => null,
            'name' => null,
            'member_code' => null,
            'date_import' => null,
            'date' => null,
            'amount' => null,
        ];

        foreach ($sheetData as $index => $row) {
            if ($row[0] === '氏名') {
                $summarys['name'] = $row[1];
                $user = User::where('name', $row[1])->first();
                if ($user) {
                    $summarys['member_code'] = $user->member_code;
                }
            } elseif ($row[0] === '伝票日付') {
                $summarys['date_import'] = $row[1];
                if (preg_match('/(\d+)月(\d+)日/', $row[1], $matches)) {
                    $month = $matches[1];
                    $day = $matches[2];
                
                    // Carbonオブジェクトを使ってY-m-d形式に変換
                    $currentYear = Carbon::now()->year;
                    $date = Carbon::createFromDate($currentYear, $month, $day);
                    $summarys['date'] = $date->format('Y-m-d');
                }
            } elseif ($row[0] === '伝票No') {
                $summarys['no'] = $sheetData[$index+1][0];
            } elseif ($row[0] === '合計') {
                if (isset($row[1])) {
                    $summarys['amount'] = $row[1];
                    break;
                } elseif (isset($row[3])) {
                    $summarys['amount'] = $row[3];
                    break;
                }  
            }
        }

        // 商品別セット数の一覧が記載されている行を特定して$details[]に取得
        $details = [];
        $startRow = null;
        $endRow = null;
        foreach ($sheetData as $index => $row) {
            if ($row[0] === '商品名') {
                $startRow = $index + 1;
            } elseif ($row[0] === '合計' && $startRow !== null) {
                $endRow = $index;
                break;
            }
        }
        
        if ($startRow !== null && $endRow !== null) {
            for ($i = $startRow; $i < $endRow; $i++) {
                // 商品名から商品IDを取得
                $product = Product::where('name', $sheetData[$i][0])->first();
                if ($product) {
                    $details[] = [
                        'product_id' => $product->id,
                        'name' => $sheetData[$i][0],
                        'amount' => $sheetData[$i][1]
                    ];
                }
            }
        }

        // ドロップダウンリスト表示に必要なデータを取得
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code')
            ->orderBy('priority', 'ASC')
            ->get();
        $trade_types = TradeType::select('trade_type', 'name', 'caption')
            ->get();

        // 取引タイプを判明する範囲で場合分け
        if ($summarys['member_code'] == 3851) {
            $type = !$summarys['no'] ?? true ? 121 : 110;
        } else if ($summarys['member_code'] !== null) {
            $type = !$summarys['no'] ?? true ? 21 : 10;
        } else {
            $type = 11;
        }

        return view('upload-check', compact('summarys', 'details', 'users', 'trade_types', 'type'));
    }

    public function save(Request $request)
    {
        // 伝票NoはNullable
        $request->validate([
            'no' => 'nullable|integer',
            'name' => 'required|integer',
            'date' => 'required|date',
            'type' => 'required|integer',
            'amount' => 'required|integer',
            'details' => 'required|array',
            'details.*.product_id' => 'required|integer',
            'details.*.amount' => 'required|integer',
        ]);

        // 重複チェック
        if (!empty($request->input('no'))) {
            $checkNo = $request->input('no');
            $existingTrading = Trading::where('check_no', $checkNo)->first();
            if ($existingTrading) {
                return back()->withErrors(['no' => '指定された「伝票No.」は既に登録されています。']);
            }
        } else {
            $checkNo = null;
        }

        // トランザクション開始
        DB::beginTransaction();

        try {
            $tradeType = $request->input('type');
            $memberCode = $request->input('name');
            $amount = $request->input('amount');
            $date = $request->input('date');

            // 新しい取引レコードを追加
            $trading = new Trading();
            $trading->check_no = $checkNo;
            $trading->member_code = $memberCode;
            $trading->date = $date;
            $trading->trade_type = $tradeType;
            $trading->amount = $amount;
            $trading->save();

            // 注文の時の処理
            if (in_array($tradeType, config('custom.sales_tradeTypes'))) {
                $user = User::where('member_code', $memberCode)->first();
                $user->latest_trade = $trading->id;
                $user->sales += $amount;
                $user->save();
            }

            // 預け入れの時の処理
            if (in_array($tradeType, config('custom.depo_tradeTypesIn'))) {
                $user = User::where('member_code', $memberCode)->first();
                $user->depo_status += $amount;
                $user->save();
                // DepoRealtimeの更新
                foreach ($request->input('details') as $detail) {
                    $depoRealtime = DepoRealtime::firstOrNew([
                        'member_code' => $memberCode,
                        'product_id' => $detail['product_id']
                    ]);
                    $depoRealtime->amount += $detail['amount'];
                    $depoRealtime->save();
                }
            }

            // 預け出しの時の処理
            if (in_array($tradeType, config('custom.depo_tradeTypesOut'))) {
                $user = User::where('member_code', $memberCode)->first();
                $user->depo_status -= $amount;
                $user->save();
                // DepoRealtimeの更新
                foreach ($request->input('details') as $detail) {
                    $depoRealtime = DepoRealtime::firstOrNew([
                        'member_code' => $memberCode,
                        'product_id' => $detail['product_id']
                    ]);
                    $depoRealtime->amount -= $detail['amount'];
                    $depoRealtime->save();
                }
            }

            // 取引詳細を追加
            foreach ($request->input('details') as $detail) {
                $tradeDetail = new TradeDetail();
                $tradeDetail->trade_id = $trading->id;
                $tradeDetail->product_id = $detail['product_id'];
                $tradeDetail->amount = $detail['amount'];
                $tradeDetail->save();
            }

            // トランザクションコミット
            DB::commit();

            // データ保存成功時
            return redirect()->route('upload')->with('success', '取引データが正常に保存されました。');

        } catch (\Exception $e) {
            // トランザクションロールバック
            DB::rollBack();
            \Log::error('Data update(add) failed: ' . $e->getMessage());
            return redirect()->route('upload')->withErrors(['error' => '取引データの保存中にエラーが発生しました:  ' . $e->getMessage()]);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'trade_id' => 'required|integer',
        ]);

        // 取引IDから取引内容を取得
        $tradeId = $request->input('trade_id');
        $trading = Trading::find($tradeId);
        if (!$trading) {
            return back()->withErrors(['error' => '指定された取引はデータベースに存在しません。']);
        } else {
            $tradeType = $trading->trade_type;
            $memberCode = $trading->member_code;
            $amount = $trading->amount;
            $date = $trading->date;
            $details = TradeDetail::where('trade_id', $tradeId)->get();
        }

        // トランザクション開始
        DB::beginTransaction();
        try {
            // trade_typeが注文の時の処理
            if (in_array($tradeType, config('custom.sales_tradeTypes'))) {
                $user = User::where('member_code', $memberCode)->first();
                // 最新注文の訂正
                $latest =  $this->functionsController->latestTrade($memberCode);
                $user->latest_trade = $latest ? $latest->id : null;
                // 年間実績の訂正
                $user->sales -= $amount;
                $user->save();
            }

            // 預け入れの時の処理
            if (in_array($tradeType, config('custom.depo_tradeTypesIn'))) {
                $user = User::where('member_code', $memberCode)->first();
                $user->depo_status -= $amount;
                $user->save();
                // DepoRealtimeの更新
                foreach ($details as $detail) {
                    $depoRealtime = DepoRealtime::firstOrNew([
                        'member_code' => $memberCode,
                        'product_id' => $detail['product_id']
                    ]);
                    $depoRealtime->amount -= $detail['amount'];
                    $depoRealtime->save();
                }
            }

            // 預け出しの時の処理
            if (in_array($tradeType, config('custom.depo_tradeTypesOut'))) {
                $user = User::where('member_code', $memberCode)->first();
                $user->depo_status += $amount;
                $user->save();
                // DepoRealtimeの更新
                foreach ($details as $detail) {
                    $depoRealtime = DepoRealtime::firstOrNew([
                        'member_code' => $memberCode,
                        'product_id' => $detail['product_id']
                    ]);
                    $depoRealtime->amount += $detail['amount'];
                    $depoRealtime->save();
                }
            }

            // trade_detailsの該当カラムを削除
            TradeDetail::where('trade_id', $tradeId)->delete();

            // tradingsの該当カラムを削除
            $trading->delete();

            // トランザクションコミット
            DB::commit();

            return redirect()->route('admin')->with('success', '取引をデータベースから正常に削除しました');
        } catch (\Exception $e) {
            // トランザクションロールバック
            DB::rollBack();
            \Log::error('Data update(delete) failed: ' . $e->getMessage());
            return back()->withErrors(['error' => '取引の削除中にエラーが発生しました: ' . $e->getMessage()]);
        }
    }

    public function refresh_sales() {
        DB::beginTransaction();
        try {
            // 【最新注文&年間実績（年初からの累計注文セット数）を更新】
            $users = User::where('status', 1)->get();
            foreach ($users as $user) {
                // 最新注文
                $latest =  $this->functionsController->latestTrade($user->member_code);
                $user->latest_trade = $latest ? $latest->id : null;
                // 年間実績
                $sales = $this->functionsController->yearlySales($user->member_code);
                $user->sales = $sales;
                $user->save();
            }

            // 【資格手当（過去6ヵ月に実績のあるグループメンバーの人数）を更新】
            $this->functionsController->subRefresh($users);
            
            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            // report($e);
            \Log::error('Data update(refresh) failed: ' . $e->getMessage());
            return back()->withErrors(['error' => 'データの更新処理に失敗しました。', 'エラーログ保存先：\storage\logs\laravel.log']);
        }
        
        return redirect()->route('dashboard')->with('success', 'データの更新が正常に行われました。【最新注文&年間実績&資格手当】');
    }
}
