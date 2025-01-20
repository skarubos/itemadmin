<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

trait HandlesTransactions
{
    /**
     * トランザクションを処理し、エラーハンドリングを行う
     *
     * @param callable $callback
     * @param string $successRoute
     * @param string $successMessage
     * @param string $errorMessage
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleTransaction(callable $callback, $successRoute, $successMessage, $errorMessage)
    {
        DB::beginTransaction();
        try {
            // コールバックを実行
            $callback();

            DB::commit();

            return redirect()->route($successRoute)->with('success', $successMessage);

        } catch (QueryException $e) { // DBクエリエラー
            DB::rollBack();
            // エラーメッセージの1行目のみ取得
            $e_message = explode("\n", $e->getMessage())[0];
            // DBクエリエラーの種類を日本語で表示する
            $e_type =  match($e->errorInfo[1]) {
                1048 => 'NULL制約違反',
                1451 => '外部キー制約違反',
                1054 => '存在しないカラム',
                1062 => 'UNIQUE制約違反',
                default => 'データベースエラー',
            };
            Log::error('DBクエリに失敗(' . $e_type . '): ' . $e_message);
            return back()->withErrors([
                'error' => $e_type . '(' . $e->errorInfo[1] . ')', $e_message
            ]);
       
        } catch (\Exception $e) { // その他のエラー
            DB::rollBack();
            // エラーメッセージの1行目のみ取得
            $e_message = explode("\n", $e->getMessage())[0];
            Log::error('Exceptionエラー: ' . $e_message);

            return back()->withErrors([
                'error' => $errorMessage, $e_message, 'エラーログ保存先：\storage\logs\laravel.log'
            ]);
        }
    }
}
