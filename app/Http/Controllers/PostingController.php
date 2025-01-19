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

class PostingController extends Controller
{
    // FunctionsControllerのメソッドを$this->functionsで呼び出せるようにする
    private $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functions = $functionsController;
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
        $trade = new Trading;

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

            $details = [];
            $remain = 0;
            return view('trading.edit', compact('trade', 'details', 'users', 'trade_types', 'remain'));
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

        // 取引詳細を格納する配列
        $details = [];
        
        // 商品別セット数の一覧を$details[]に取得
        if ($startRow !== null && $endRow !== null) {
            for ($i = $startRow; $i < $endRow; $i++) {
                // 商品名から商品IDを取得
                $product = Product::where('name', $sheetData[$i][0])->first();
                if ($product) {
                    // Detailインスタンスを配列に格納
                    $detail = new TradeDetail;
                    $detail->product_id = $product->id;
                    $detail->name = $product->name;
                    $detail->amount = $sheetData[$i][$col123];
                    $details[] = $detail;
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
        return view('trading.edit', compact('trade', 'details', 'users', 'trade_types', 'remain'));
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
            $this->functions->update_trade($tradeId, $validatedData, $details, $change_detail);
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

}
