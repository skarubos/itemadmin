<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Traits\HandlesTransactions;
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
    use HandlesTransactions;

    // FunctionsControllerのメソッドを$this->functionsで呼び出せるようにする
    private $functionsController;
    public function __construct(FunctionsController $functionsController)
    {
        $this->functions = $functionsController;
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
