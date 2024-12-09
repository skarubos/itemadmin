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
use Carbon\Carbon;

class ExcelImportController extends Controller
{
    public function upload_check(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        $summarys = [
            'no' => null,
            'name' => null,
            'member_code' => null,
            'date_import' => null,
            'date' => null,
            'amount' => null,
        ];

        $num = 0;
        foreach ($sheetData as $row) {
            // 各セルの値をUTF-8に変換
            // $row = array_map(function($value) {
            //     return mb_convert_encoding($value, 'UTF-8', 'auto');
            // }, $row);
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
                $summarys['no'] = $sheetData[$num+1][0];
            } elseif ($row[0] === '合計' && isset($row[3])) {
                $summarys['amount'] = $row[3];
                break;
            }
            $num++;
        }

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

        $users = User::where('status', 1)
            ->select('id', 'name', 'member_code')
            ->orderBy('priority', 'ASC')
            ->get();

        $trade_types = TradeType::select('trade_type', 'name', 'caption')
            ->get();

        if ($summarys['member_code'] == 3851) {
            $type = 110;
        } else if ($summarys['member_code'] !== null) {
            $type = 10;
        } else {
            $type = 11;
        }

        return view('upload-check', compact('summarys', 'details', 'users', 'trade_types', 'type'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'no' => 'required|integer',
            'name' => 'required|integer',
            'date' => 'required|date',
            'type' => 'required|integer',
            'amount' => 'required|integer',
            'details' => 'required|array',
            'details.*.product_id' => 'required|integer',
            'details.*.amount' => 'required|integer',
        ]);

        // 重複チェック
        $checkNo = $request->input('no');
        $existingTrading = Trading::where('check_no', $checkNo)->first();
        if ($existingTrading) {
            return back()->withErrors(['no' => '指定された「伝票No.」は既に登録されています。']);
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
            if (in_array($tradeType, [10, 11, 12, 20, 110, 111])) {
                $user = User::where('member_code', $memberCode)->first();
                $user->latest_trade = $trading->id;
                $user->sales += $amount;
                $user->save();
            }

            // 預け入れor預け出しの時の処理
            if (in_array($tradeType, [11, 21, 111, 121])) {
                foreach ($request->input('details') as $detail) {
                    $depoRealtime = DepoRealtime::firstOrNew([
                        'member_code' => $memberCode,
                        'product_id' => $detail['product_id']
                    ]);
                    $depoRealtime->amount = $detail['amount'];
                    $depoRealtime->save();
                }
            }

            // 預け入れの時の処理
            if (in_array($tradeType, [11, 111])) {
                $user = User::where('member_code', $memberCode)->first();
                $user->depo_status += $amount;
                $user->save();
            }

            // 預け出しの時の処理
            if (in_array($tradeType, [21, 121])) {
                $user = User::where('member_code', $memberCode)->first();
                $user->depo_status -= $amount;
                $user->save();
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
            return redirect()->route('upload')->withErrors(['error' => '取引データの保存中にエラーが発生しました:  ' . $e->getMessage()]);
        }
    }
}