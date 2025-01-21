<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\Http\Traits\HandlesTransactions;
use App\Http\Requests\IdRequest;
use App\Http\Requests\TradeTypeRequest;
use App\Models\TradeType;
use App\Models\Trading;

class TradeTypeController extends Controller
{
    use HandlesTransactions;

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
        $callback = function () use ($request) {
            $tradeType = TradeType::createOrUpdate($request->validated());
        };

        // 作成か編集かの判定
        $create = $request->routeIs('create') ? true : false;
        $sMessage = '取引種別[' . $request->input('trade_type') . ': ' . $request->input('name') . ']の'
                . ($create ? '作成' : '更新') . 'に成功しました。';
        $fMessage = '取引種別の' . ($create ? '作成' : '更新') . 'に失敗しました。';
        
        return $this->handleTransaction(
            $callback,
            'sales', // 成功時のリダイレクトルート
            $sMessage, // 成功メッセージ
            $fMessage // エラーメッセージ
        );
    }

    public function delete(IdRequest $request)
    {
        $callback = function () use ($request) {
            TradeType::find($request->validated()['id'])->delete();
        };
        return $this->handleTransaction(
            $callback,
            'sales', // 成功時のリダイレクトルート
            '取引種別を削除しました。', // 成功メッセージ
            '取引種別の削除に失敗しました。: ' // エラーメッセージ
        );
    }
}
