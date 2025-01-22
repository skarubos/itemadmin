<?php

namespace App\Http\Controllers;

use PhpOffice\PhpSpreadsheet\IOFactory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Http\Requests\IdRequest;
use App\Http\Requests\TradingRequest;
use App\Http\Traits\HandlesTransactions;
use App\Models\User;
use App\Models\Trading;
use App\Models\TradeDetail;
use App\Models\TradeType;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Http\Controllers\FunctionsController;
use Carbon\Carbon;

class TradingController extends Controller
{
    use HandlesTransactions;

    // FunctionsControllerのメソッドを$this->functionsで呼び出せるようにする
    private $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functions = $functionsController;
    }

    public function show_check() {
        // 自動登録された取引を取得
        $params = [
            'sortColumn' => 'date',
            'sortDirection' => 'ASC',
            'status' => 2,
        ];
        $newTrade = Trading::getTradings($params);

        return view('trading.check', compact('newTrade'));
    }

    public function show_edit_request(IdRequest $request) {
        // POSTされたIDをURLに含めてリダイレクト
        $route = '/trade/edit/' . $request->input('id') . '/0';
        return redirect($route);
    }
    /**
     * 取引の編集ページを表示（通常編集or自動登録取引の確認時の編集）
     *
     * @param int $tradeId 取引ID
     * @param int $remain 0:通常の編集、1~:自動登録取引の確認時の残り件数
     */
    public function show_edit($tradeId, $remain)
    {
        $trade = Trading::getTrade($tradeId);
        $details = TradeDetail::getTradeDetail($tradeId);

        // 新規登録時と形式を合わせるため、各要素にproductのnameを設定
        $details->each(function ($tradeDetail) {
            $tradeDetail->name = $tradeDetail->product->name;
        });

        // ドロップダウンリスト表示に必要なデータを取得
        $users = User::getForDropdownList();
        // 自動登録取引の確認時は選択できる取引種別を制限
        $trade_types = $remain ? TradeType::getTradeTypes('tradeTypes_for_checkTrade') : TradeType::getTradeTypes();
        
        return view('trading.edit', compact('trade', 'details', 'users', 'trade_types', 'remain'));
    }

    public function show_create()
    {
        // 取引データを格納するインスタンス
        $trade = new Trading;

        // ドロップダウンリスト表示に必要なデータを取得
        $users = User::getForDropdownList();
        $trade_types = TradeType::getTradeTypes();

        return view('trading.edit', compact('trade', 'users', 'trade_types'));
    }

    public function show_create_idou()
    {
        // 取引データを格納するインスタンス
        $trade = new Trading;
        $trade->trade_type = 20;

        // ドロップダウンリスト表示に必要なデータを取得
        $users = User::getForDropdownList();
        $trade_types = TradeType::getTradeTypes($trade->trade_type);

        return view('trading.edit', compact('trade', 'users', 'trade_types'));
    }

    public function upload_check(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|mimes:xlsx,xls',
        ]);
        if ($validator->fails()) {
            return back()->withErrors(['error' => 'アップロード可能なファイル形式（.xlsx .xls）ではありません。'])->withInput();
        };

        // 取引データを格納するインスタンス
        $trade = new Trading;

        // Excelを解析
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
                    $user = User::where('name', $trade->name)
                        ->select('member_code')
                        ->first();
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
                            $types = ['sales', 'in', 'out'];
                            $type = $types[$i - 1];
                            $col123 = $i;
                            break;
                        }
                    }
                    break;
            }
        }

        // 商品別セット数の一覧を取引詳細を格納する配列$details[]に取得
        $details = [];
        if ($startRow !== null && $endRow !== null) {
            for ($i = $startRow; $i < $endRow; $i++) {
                // 商品名から商品IDを取得
                $productId = Product::getProductId($sheetData[$i][0]);

                // Detailインスタンスを配列に格納
                $detail = new TradeDetail;
                $detail->product_id = $productId;
                $detail->name = $sheetData[$i][0];
                $detail->amount = $sheetData[$i][$col123];

                $details[] = $detail;
            }
        } else {
            return back()->withErrors(['error' => '取引詳細が記入された行を見つけられません。'])->withInput();
        }

        // ドロップダウンリスト表示に必要なデータを取得
        $users = User::getForDropdownList();
        $trade_types = TradeType::getTradeTypes();

        // 取引タイプを判明する範囲で場合分け
        $trade->trade_type = $this->functions->selectTradeType($trade->member_code, $type);

        return view('trading.edit', compact('trade', 'details', 'users', 'trade_types'));
    }

    public function store(TradingRequest $request)
    {
        // バリデーション済みのデータを取得
        $trade = $request->validated();
        // 編集対象の取引IDを取得（新規の場合はNULL）
        $tradeId = $trade['trade_id'] ?? null;
        $details = $trade['details'] ?? null;
        // 取引詳細(1:変更あり(新規登録を含む))
        $change_detail = $trade['change_detail'] ?? null;
        
        $callback = function () use ($tradeId, $trade, $details, $change_detail) {
            $this->functions->update_trade($tradeId, $trade, $details, $change_detail);
        };

        // メッセージとDB保存成功時のリダイレクト用ルートを設定
        $remain = $trade['remain'];
        if ($remain > 0) {
            // 自動登録取引の確認時のルートとメッセージを取得
            $params = $this->getRouteAndMessage($tradeId, $remain);
            $route = $params['route'];
            $sMessage = $params['sMessage'];
            $eMessage = $params['fMessage'];
        } elseif ($tradeId) {
            $route = 'admin';
            $sMessage = '編集した取引データが正常に保存されました。';
            $eMessage = '編集した取引データの保存中にエラーが発生しました。';
        } else {
            $route = 'trade.upload';
            $sMessage = '新規取引データが正常に保存されました。';
            $eMessage = '新規取引データの保存中にエラーが発生しました。';
        }
        
        return $this->handleTransaction(
            $callback,
            $route, // 成功時のリダイレクトルート
            $sMessage, // 成功メッセージ
            $eMessage // エラーメッセージ
        );
    }

    public function change_status($tradeId, $remain) {
        $callback = function () use ($tradeId) {
            $trade = Trading::find($tradeId);
            if (!$trade) {
                throw new \Exception('取引ID'.$tradeId.'が見つかりません。');
            }
            $trade->status = 1;
            $trade->save();
        };

        // ルートとメッセージを取得
        $params = $this->getRouteAndMessage($tradeId, $remain);
        $route = $params['route'];
        $sMessage = $params['sMessage'];
        $eMessage = $params['fMessage'];

        return $this->handleTransaction(
            $callback,
            $route, // 成功時のリダイレクトルート
            $sMessage, // 成功メッセージ
            $eMessage // エラーメッセージ
        );
    }

    /**
     * 自動登録取引の確認時のルートとメッセージを取得
     *
     * @param string $tradeId 取引ID
     * @param int|null $remain 残り件数
     * @return string $route $sMessage $eMessage
     */
    private function getRouteAndMessage($tradeId, $remain)
    {
        $remain--;
        if ($remain > 0) {
            $route = 'trade.check';
            $sMessage = '取引ID【'.$tradeId.'】の確認完了！残り'.$remain.'件';
        } elseif ($remain == 0) {
            $route = 'sales';
            $sMessage = '取引ID【'.$tradeId.'】の確認完了！';
        } else {
            $route = 'sales';
            $sMessage = '取引ID【'.$tradeId.'】の確認完了！残り件数(remain)の値が不正です。';
        }
        $eMessage = '取引データの保存中にエラーが発生しました。';
        return [
            'route' => $route,
            'sMessage' => $sMessage,
            'fMessage' => $eMessage,
        ];
    }

    public function delete(IdRequest $request)
    {
        // 取引IDから取引内容を取得
        $tradeId = $request->input('id');
        $trading = Trading::find($tradeId);
        if (!$trading) {
            return back()->withErrors(['error' => '指定された取引(ID:'.$tradeId.')はデータベースに存在しません。']);
        } else {
            $details = TradeDetail::getTradeDetail($tradeId);
        }

        $callback = function () use ($trading, $details) {
            // 外部キー制約無効化のためusersテーブルの関連レコードを更新
            User::where('latest_trade', $trading->id)->update(['latest_trade' => null]);

            // trade_detailsの該当カラムを削除
            TradeDetail::where('trade_id', $trading->id)->delete();

            // tradingsの該当カラムを削除
            $trading->delete();

            // 注文の時の処理
            if (in_array($trading->trade_type, config('custom.sales_tradeTypes'))) {
                // 最新注文&年間実績&資格手当を更新
                $this->functions->refreshMember($trading->member_code);
            }

            // 預入れor預出しの時の処理
            if (in_array($trading->trade_type, config('custom.depo_tradeTypes'))) {
                // 現在合計預けセット数＆DepoRealtimeテーブルを更新
                $this->functions->saveDepoForMember($trading->member_code, $trading->trade_type, $trading->amount, $details, -1);
            }
        };
        return $this->handleTransaction(
            $callback,
            'admin', // 成功時のリダイレクトルート
            '取引をデータベースから正常に削除しました', // 成功メッセージ
            '取引の削除中にエラーが発生しました。: ' // エラーメッセージ
        );
    }
}
