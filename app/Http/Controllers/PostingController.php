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

    public function show_edit_trade(Request $request)
    {
        $tradeId = $request->input('edit_id');
        $trade = Trading::
            with(['tradeType' => function($query) {
                $query->select('trade_type', 'name');
            }])
            ->with(['user' => function($query) {
                $query->select('member_code', 'name');
            }])
            ->where('id', $tradeId)
            ->select('id', 'check_no', 'member_code', 'date', 'trade_type', 'amount')
            ->first();
        

        $details = $details = TradeDetail::with('product')
            ->where('trade_id', $tradeId)
            ->get();

        // ドロップダウンリスト表示に必要なデータを取得
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code')
            ->orderBy('priority', 'ASC')
            ->get();
        $trade_types = TradeType::select('trade_type', 'name', 'caption')
            ->get();

        return view('trade-edit', compact('trade', 'details', 'users', 'trade_types'));
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

    public function save_trade(Request $request)
    {
        // 伝票NoはNullable
        $validatedData = $request->validate([
            'trade_id' => 'nullable|integer',
            'check_no' => 'nullable|integer',
            'member_code' => 'required|integer',
            'date' => 'required|date',
            'trade_type' => 'required|integer',
            'amount' => 'required|integer',
            'details' => 'required|array',
            'details.*.product_id' => 'required|integer',
            'details.*.amount' => 'required|integer',
        ]);
        // 取引IDを取得（新規の場合はNULL）
        $tradeId = $validatedData['trade_id'] ?? null;
        // 伝票番号があれば取得
        $checkNo = $validatedData['check_no'] ?? null;
        $memberCode = $validatedData['member_code'];
        $tradeType = $validatedData['trade_type'];
        $amount = $validatedData['amount'];
        $details = $validatedData['details'];

        // 伝票番号で重複チェック
        if (is_null($tradeId) && !is_null($checkNo)) {
            $existingTrading = Trading::where('check_no', $checkNo)->first();
            if ($existingTrading) {
                return back()->withErrors(['check_no' => '指定された「伝票No.」は既に登録されています。']);
            }
        }

        if ($tradeId) {
            $oldTrading = Trading::find($tradeId);
            $diff = $amount - $oldTrading->amount;
            $oldDetails = TradeDetail::where('trade_id', $tradeId)->get();
        }

        // トランザクション開始
        DB::beginTransaction();

        try {
            // 取引を追加or更新
            $trading = $tradeId ? $oldTrading : new Trading();
            if ($trading) {
                $trading->fill($validatedData);
                $trading->save();

                // 取引詳細を追加or更新
                if ($tradeId) {
                    // 既にある取引詳細を全削除
                    TradeDetail::where('trade_id', $tradeId)->delete();
                }
                foreach ($details as $detail) {
                    $tradeDetail = new TradeDetail();
                    $tradeDetail->trade_id = $trading->id;
                    $tradeDetail->product_id = $detail['product_id'];
                    $tradeDetail->amount = $detail['amount'];
                    $tradeDetail->save();
                }
            } else {
                throw new \Exception("保存先($trading)を取得できませんでした");
            }

            // 注文の時の処理
            if (in_array($tradeType, config('custom.sales_tradeTypes'))) {
                // 最新注文&年間実績&資格手当を更新
                $this->functionsController->refresh($memberCode);
                if ($memberCode != $oldTrading->mamber_code) {
                    $this->functionsController->refresh($oldTrading->mamber_code);
                }
            }

            // 預入れor預出しの時の処理
            if (in_array($tradeType, config('custom.depo_tradeTypes'))) {
                // 現在合計預けセット数＆DepoRealtimeテーブルを更新
                $this->functionsController->saveDepoForMember($memberCode, $tradeType, $amount, $details, 1);
            }

            DB::commit();

            // データ保存成功時
            if ($tradeId) {
                return redirect()->route('admin')->with('success', '編集した取引データが正常に保存されました。');
            } else {
                return redirect()->route('upload')->with('success', '新規取引データが正常に保存されました。');
            }

        } catch (\Exception $e) {
            // トランザクションロールバック
            DB::rollBack();
            if ($tradeId) {
                \Log::error('編集した取引の保存に失敗: ' . $e->getMessage());
            return redirect()->route('admin')->withErrors(['error' => '編集した取引データの保存中にエラーが発生しました:  ' . $e->getMessage()]);
            } else {
                \Log::error('新規取引の保存に失敗: ' . $e->getMessage());
            return redirect()->route('upload')->withErrors(['error' => '新規取引データの保存中にエラーが発生しました:  ' . $e->getMessage()]);
            }
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
            $details = TradeDetail::where('trade_id', $tradeId)->get();
        }

        // トランザクション開始
        DB::beginTransaction();
        try {
            // trade_detailsの該当カラムを削除
            TradeDetail::where('trade_id', $tradeId)->delete();

            // tradingsの該当カラムを削除
            $trading->delete();

            // 注文の時の処理
            if (in_array($tradeType, config('custom.sales_tradeTypes'))) {
                // 最新注文&年間実績&資格手当を更新
                $this->functionsController->refresh($memberCode);
            }

            // 預入れor預出しの時の処理
            if (in_array($tradeType, config('custom.depo_tradeTypes'))) {
                // 現在合計預けセット数＆DepoRealtimeテーブルを更新
                $this->functionsController->saveDepoForMember($memberCode, $tradeType, $amount, $details, -1);
            }


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
}
