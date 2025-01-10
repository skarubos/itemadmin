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
use Exception;

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

    public function show_edit_trade_request(Request $request) {
        // POSTされたIDをURLに含めてリダイレクト
        $route = '/trade/edit/' . $request->input('edit_id') . '/0';
        return redirect($route);
    }
    public function show_edit_trade($tradeId, $remain)
    {
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
        $details = [];
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

        return view('trade-edit', compact('trade', 'details', 'users', 'trade_types', 'remain'));
    }
    
    public function upload_check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|mimes:xlsx,xls',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        };

        // 取引データを格納するインスタンス
        $trade = new Trade(null, null, null, null, null, null);
        // 取引詳細を格納する配列
        $details = [];

        // ファイルが存在しない場合の処理
        if (!$request->hasFile('file')) {
            // 移動を想定
            $trade->trade_type = 20;
            // ドロップダウンリスト表示に必要なデータを取得
            $users = User::where('status', 1)
                ->select('id', 'name', 'member_code')
                ->orderBy('priority', 'ASC')
                ->get();
            $trade_types = TradeType::select('trade_type', 'name', 'caption')
                ->get();

            $remain = 0;
            return view('trade-edit', compact('trade', 'details', 'users', 'trade_types', 'remain'));
        }

        $file = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

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
                $trade->trade_type = $trade->member_code === 3851 ? 121 : 21;
                break;
        }

        $remain = 0;
        return view('trade-edit', compact('trade', 'details', 'users', 'trade_types', 'remain'));
    }

    public function save_trade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trade_id' => 'nullable|integer',
            'status' => 'required|integer',
            'remain' => 'required|integer',
            'member_code' => 'required|integer',
            'date' => 'required|date',
            'trade_type' => 'required|integer',
            'amount' => 'required|integer',
            'change_detail' => 'nullable|integer',
            'details' => 'nullable|array',
            'details.*.product_id' => 'nullable|integer',
            'details.*.amount' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        };

        // バリデーション済みのデータを取得
        $validatedData = $validator->validated();
        // 編集対象の取引IDを取得（新規の場合はNULL）
        $tradeId = $validatedData['trade_id'] ?? null;
        $details = $validatedData['details'] ?? null;
        // 取引詳細(1:変更あり(新規登録を含む))
        $change_detail = $validatedData['change_detail'] ?? null;
        
        DB::beginTransaction();

        try {
            // 新規登録or更新
            $this->functionsController->update_trade($tradeId, $validatedData, $details, $change_detail);
            DB::commit();

            // データ保存成功時
            $remain = $validatedData['remain'] - 1;
            if ($remain > 0) {
                return redirect()->route('trade.check')
                    ->with('success', '取引ID【'.$tradeId.'】の確認完了！残り'.$remain.'件');
            } elseif ($remain == 0) {
                return redirect()->route('sales')->with('success', '取引ID【'.$tradeId.'】の確認完了！');
            }
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

            DB::commit();

            return redirect()->route('admin')->with('success', '取引をデータベースから正常に削除しました');
        } catch (\Exception $e) {
            // トランザクションロールバック
            DB::rollBack();
            \Log::error('Data update(delete) failed: ' . $e->getMessage());
            return back()->withErrors(['error' => '取引の削除中にエラーが発生しました: ' . $e->getMessage()]);
        }
    }

    public function save_product_check(Request $request) {
        $productTypes = $request->input('product_type');
        $id = $request->input('id');
        $name = $request->input('name');

        DB::beginTransaction();
        try {
            foreach ($productTypes as $i => $type) {
                if (!$type) {
                    throw new Exception("全ての商品種別を選択してください。");
                }
                // productsテーブルから未使用の最小種別idを取得
                $maxId = Product::where('id', '>', $type * 100)
                    ->where('id', '<=', $type * 100 + 99)
                    ->max('id');
                
                // 新しい商品ID
                $newId = $maxId + 1;

                // 新しいProductレコードを作成
                $product = new Product;
                $product->id = $newId;
                $product->name = $name[$i];
                $product->product_type = $type;
                $product->save();

                // 関連するテーブルのproduct_idを更新
                TradeDetail::where('product_id', $id[$i])->update(['product_id' => $newId]);
                DepoRealtime::where('product_id', $id[$i])->update(['product_id' => $newId]);

                // 古いProductレコードを削除
                Product::find($id[$i])->delete();
            }

            DB::commit();

            return redirect()->route('sales')->with('success', '新規商品の商品種別を登録しました。');
        } catch (\Exception $e) {
            // トランザクションロールバック
            DB::rollBack();
            \Log::error('商品種別の登録に失敗しました。: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
