<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Traits\HandlesTransactions;
use App\Http\Requests\IdRequest;
use App\Http\Requests\ProductRequest;
use App\Models\User;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Models\TradeDetail;

class ProductController extends Controller
{
    use HandlesTransactions;

    public function show_create()
    {
        return back()->withErrors(['error' => '商品の新規作成は現在できません。']);
    }
    public function show_edit(IdRequest $request) {
        $id = $request->validated()['id'];
        $product = Product::find($id);

        // 商品種別の配列
        $types = config('custom.product_types');

        return view('product.edit', compact('product', 'types'));
    }

    public function show_product_check() {
        // 商品種別の配列
        $types = config('custom.product_types');

        $newProducts = Product::where('product_type', 5)->get();

        if ($newProducts->isEmpty()) {
            return back()->withErrors(['error' => '新規商品はありません。']);
        }

        return view('product.check', compact('types', 'newProducts'));
    }

    public function update(ProductRequest $request)
    {
        $validated = $request->validated();
        $id = $validated['id'];
        $name = $validated['name'];
        $productType = $validated['product_type'];
        $remain = $validated['remain'] ?? null;

        $callback = function () use ($id, $name, $productType) {
           // productsテーブルから未使用の最小idを取得
            $newId = Product::getNewId($productType);

            // 新しいProductレコードを作成
            $product = new Product;
            $product->id = $newId;
            $product->name = $name;
            $product->product_type = $productType;
            $product->save();

            // 関連するテーブルのproduct_idを更新
            TradeDetail::where('product_id', $id)->update(['product_id' => $newId]);
            DepoRealtime::where('product_id', $id)->update(['product_id' => $newId]);

            // 古いProductレコードを削除
            Product::find($id)->delete();
        };

        $params = $this->getRedirectParams($name, $remain);
        
        return $this->handleTransaction(
            $callback,
            $params['route'], // 成功時のリダイレクトルート
            $params['success'], // 成功メッセージ
            $params['failure'] // エラーメッセージ
        );
    }
    /**
     * リダイレクト先とエラメッセージを設定するメソッド
     *
     * @param string $name エラー表示名
     * @param int|null $remain 残り件数
     * @return array 
     */
    private function getRedirectParams($name, $remain)
    {
        if (is_null($remain)) {
            $params = [
                'route' => 'setting',
                'success' => '商品【'.$name.'】の商品種別を更新しました。',
            ];                
        } elseif ($remain > 1) {
            $remain--;
            $params = [
                'route' => 'product.check',
                'success' => '新規商品【'.$name.'】の商品種別を更新！残り'.$remain.'件',
            ];  
        } elseif ($remain == 1) {
            $params = [
                'route' => 'sales',
                'success' => '新規商品【'.$name.'】の商品種別を更新！',
            ];  
        } else {
            $params = [
                'route' => 'sales',
                'success' => '新規商品【'.$name.'】の確認完了！残り件数(remain)の値が不正です。',
            ];  
        }
        $params['failure'] = '商品種別の更新に失敗しました。';
        return $params;
    }

    public function delete(IdRequest $request)
    {
        $callback = function () use ($request) {
            $result = Product::find($request->validated()['id'])->delete();
        };
        return $this->handleTransaction(
            $callback,
            'setting', // 成功時のリダイレクトルート
            '商品を削除しました。', // 成功メッセージ
            '商品の削除に失敗しました。' // エラーメッセージ
        );
    }
}
