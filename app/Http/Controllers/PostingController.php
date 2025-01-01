<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\User;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Models\Trading;
use App\Models\TradeDetail;
use App\Models\TradeType;
use App\Http\Controllers\FunctionsController;
use Carbon\Carbon;

// 取引の「新規登録 upload_check()」と「編集 show_edit_trade()」でビューへ渡すデータ形式を揃えるために使用
// 取引記録のクラス
class Trade
{
    public $id;
    public $name;
    public $member_code;
    public $date;
    public $trade_type;
    public $amount;

    public function __construct($id, $name, $member_code, $date, $trade_type, $amount)
    {
        $this->id = $id;
        $this->name = $name;
        $this->member_code = $member_code;
        $this->date = $date;
        $this->trade_type = $trade_type;
        $this->amount = $amount;
    }
}
// 取引詳細のクラス
class Detail
{
    public $product_id;
    public $name;
    public $amount;

    public function __construct($product_id, $name, $amount)
    {
        $this->product_id = $product_id;
        $this->name = $name;
        $this->amount = $amount;
    }
}

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
            ->select('id', 'member_code', 'date', 'trade_type', 'amount')
            ->first();
        
        // 取引詳細
        $tableDetails = TradeDetail::with('product')
            ->where('trade_id', $tradeId)
            ->get();
        // 新規登録時と形式を合わせるためDetailインスタンスに入れ替え
        foreach ($tableDetails as $tableDetail) {
            $details[] = new Detail($tableDetail->product_id, $tableDetail->product->name, $tableDetail->amount);
        }

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
        $validator = Validator::make($request->all(), [
            'file' => 'required|mimes:xlsx,xls',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        };

        $file = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $trade = new Trade(null, null, null, null, null, null);

        // 詳細行（商品別セット数の一覧が記載されている行）の開始行と終了行
        $startRow = null;
        $endRow = null;
        $col123;    // 取引種別[1:出荷 2:預入れ 3:預出し]をセット数が入力されている列数で判定して格納

        // 取引データを取得（１列目の文字列で項目を判定）
        foreach ($sheetData as $index => $row) {
            switch ($row[0]) {
                case '売上計上日':
                    if (preg_match('/(\d+)月(\d+)日/', $row[1], $matches)) {
                        // Carbonオブジェクトを使ってY-m-d形式に変換
                        $date = Carbon::createFromDate(Carbon::now()->year, $matches[1], $matches[2])->format('Y-m-d');
                        $trade->date = $date;
                    }
                    break;
                case '氏名':
                    $trade->name = $row[1];
                    $user = User::where('name', $row[1])->first();
                    if ($user) {
                        $trade->member_code = $user->member_code;
                    }
                    break;
                case '商品':
                    $startRow = $index + 1;
                    break;
                case '商品合計セット数':
                    $endRow = $index;
                    foreach ([1, 2, 3] as $i) {
                        if (!($row[$i] == 0)) {
                            $trade->amount = $row[$i];
                            // ここで取引種別を取得
                            $col123 = $i;
                            break;
                        }
                    }
                    break;
            }
        }

        // 商品別セット数の一覧を$details[]に取得
        $details = [];
        if ($startRow !== null && $endRow !== null) {
            for ($i = $startRow; $i < $endRow; $i++) {
                // 商品名から商品IDを取得
                $product = Product::where('name', $sheetData[$i][0])->first();
                if ($product) {
                    // Detailインスタンスを配列に格納
                    $details[] = new Detail($product->id, $product->name, $sheetData[$i][$col123]);
                }
            }
        } else {
            return back()->withErrors(['error' => '取引詳細が記入された行を見つけられません。'])->withInput();
        }

        // ドロップダウンリスト表示に必要なデータを取得
        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code')
            ->orderBy('priority', 'ASC')
            ->get();
        $trade_types = TradeType::select('trade_type', 'name', 'caption')
            ->get();

        // 取引タイプを判明する範囲で場合分け
        switch ($col123) {
            case 1:
                $trade->trade_type = $trade->member_code === 3851 ? 110 : 10;
                break;
            case 2:
                $trade->trade_type = $trade->member_code === 3851 ? 111 : 11;
                break;
            case 3:
                $trade->trade_type  = $trade->member_code === 3851 ? 121 : 21;
                break;
        }

        return view('trade-edit', compact('trade', 'details', 'users', 'trade_types'));
    }

    public function save_trade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trade_id' => 'nullable|integer',
            'member_code' => 'required|integer',
            'date' => 'required|date',
            'trade_type' => 'required|integer',
            'amount' => 'required|integer',
            'change_detail' => 'nullable|integer',
            'details' => 'required|array',
            'details.*.product_id' => 'required|integer',
            'details.*.amount' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        };

        // バリデーション済みのデータを取得
        $validatedData = $validator->validated();
        // 取引IDを取得（新規の場合はNULL）
        $tradeId = $validatedData['trade_id'] ?? null;
        $memberCode = $validatedData['member_code'];
        $tradeType = $validatedData['trade_type'];
        $amount = $validatedData['amount'];
        $details = $validatedData['details'];

        // チェックボックスのデフォルト値を設定(0:変更なし、1:変更ありor新規登録)
        $change_detail = $request->input('change_detail', 0);

        // 編集の時は編集前データを保持
        if ($tradeId) {
            $oldTrading = Trading::find($tradeId);
            $oldDetails = TradeDetail::where('trade_id', $tradeId)->get();
        }

        // トランザクション開始
        DB::beginTransaction();

        try {
            // 取引を新規登録or編集
            $trading = $tradeId ? clone $oldTrading : new Trading();
            if (!$trading) {
                throw new \Exception("保存先($trading)を取得できませんでした");
            }
            
            // 取引を新規登録or編集
            $trading->fill($validatedData);
            $trading->save();

            // 変更ありの時、取引詳細を新規登録or編集
            if ($change_detail == 1) {
                $totalAmount = 0;
                if ($tradeId) {
                    // 既にある取引詳細を全削除
                    TradeDetail::where('trade_id', $tradeId)->delete();
                }
                foreach ($details as $detail) {
                    $totalAmount += $detail['amount'];
                    $tradeDetail = new TradeDetail();
                    $tradeDetail->trade_id = $trading->id;
                    $tradeDetail->product_id = $detail['product_id'];
                    $tradeDetail->amount = $detail['amount'];
                    $tradeDetail->save();
                }

                // 合計セット数一致確認
                if ($totalAmount != $amount) {
                    throw new \Exception("「取引セット数」と「取引詳細の合計セット数」が一致しません");
                }
            }

            // 編集前の取引が存在する場合
            if ($tradeId) {
                // 取引ユーザーが変更された場合は変更前のユーザーの最新注文&年間実績&資格手当を更新
                if ($memberCode != $oldTrading->member_code) {
                    $this->functionsController->refresh($oldTrading->member_code);
                }
                // 預入れor預出しの時の処理
                if (in_array($oldTrading->trade_type, config('custom.depo_tradeTypes'))) {
                    // 現在合計預けセット数＆DepoRealtimeテーブルを更新（削除）
                    $this->functionsController->saveDepoForMember($oldTrading->member_code, $oldTrading->trade_type, $oldTrading->amount, $oldDetails, -1);
                }
            }

            // 最新注文&年間実績&資格手当を更新
            $this->functionsController->refresh($memberCode);

            // 預入れor預出しの時の処理
            if (in_array($tradeType, config('custom.depo_tradeTypes'))) {
                // 現在合計預けセット数＆DepoRealtimeテーブルを更新（追加）
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
            // 外部キー制約無効化のためusersテーブルの関連レコードを更新
            User::where('latest_trade', $tradeId)->update(['latest_trade' => null]);

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
