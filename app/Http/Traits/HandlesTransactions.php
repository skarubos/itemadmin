<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('DB操作に失敗: ' . $e->getMessage());
            return back()->withErrors([
                'error' => $errorMessage, $e->getMessage(), 'エラーログ保存先：\storage\logs\laravel.log'
            ]);
        }
    }
}
