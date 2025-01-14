<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Http\Requests\IdRequest;
use App\Http\Requests\TradeTypeRequest;
use App\Models\TradeType;
use App\Models\Trading;

class TradeTypeController extends Controller
{
    public function show_create() {
        return view('tradeType.create');
    }

    public function show_edit(IdRequest $request) {
        $id = $request->validated()['id'];
        $tradeType = TradeType::find($id);

        return view('tradeType.edit', compact('tradeType'));
    }

    public function store(TradeTypeRequest $request)
    {
        // 作成か編集かの判定
        $create = $request->routeIs('create') ? true : false;

        DB::beginTransaction();
        try {
            $tradeType = TradeType::createOrUpdate($request->validated());
    
            DB::commit();
            $message = '取引種別[' . $tradeType->trade_type . ', ' . $tradeType->name . ']の'
                . ($create ? '作成' : '更新') . 'に成功しました。';
    
            return redirect()
                ->route('setting')
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('取引種別の' . ($create ? '作成' : '更新') . 'に失敗: ' . $e->getMessage());
            return back()->withErrors(['error' => '取引種別の' . ($create ? '作成' : '更新') . 'に失敗しました。エラーログ保存先：\storage\logs\laravel.log']);
        }
    }

    public function delete(IdRequest $request)
    {
        DB::beginTransaction();
        try {
            $result = TradeType::deleteById($request->validated()['id']);

            DB::commit();
            return redirect()
                ->route('setting')
                ->with('success', '取引種別を削除しました。');

        } catch (QueryException $e) {
            DB::rollBack();
            // 外部キー制約違反の場合のエラーメッセージ
            if ($e->errorInfo[1] == 1451) {
                return back()->withErrors(['error' => 'この取引種別は既に登録されている取引が存在するため削除できません。']);
            }
            // その他のエラー
            \Log::error('取引種別の削除に失敗: ' . $e->getMessage());
            return back()->withErrors(['error' => '取引種別の削除に失敗しました。: ' . $e->getMessage()]);
        }
    }
}
