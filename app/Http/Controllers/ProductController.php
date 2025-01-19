<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Http\Requests\IdRequest;
use App\Http\Requests\ProductRequest;
use App\Models\User;
use App\Models\Product;
use App\Models\DepoRealtime;
use App\Models\TradeDetail;

class ProductController extends Controller
{
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

    public function update(ProductRequest $request) {
        $validated = $request->validated();
        $productType = $validated['product_type'];
        $id = $validated['id'];
        $name = $validated['name'];
        $remain = $validated['remain'] ?? null;

        DB::beginTransaction();
        try {
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

            DB::commit();

            // 残り件数に応じてリダイレクト先を設定
            return $this->handleRedirect($name, $remain);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('商品種別の更新に失敗しました。: ' . $e->getMessage());
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    /**
     * リダイレクト処理を行う
     *
     * @param string $name エラー表示名
     * @param int|null $remain 残り件数
     * @return \Illuminate\Http\RedirectResponse
     */
    private function handleRedirect($name, $remain)
    {
        if (is_null($remain)) {
            return redirect()->route('setting')
                ->with('success', '商品【'.$name.'】の商品種別を更新しました。');
        } elseif ($remain > 1) {
            $remain--;
            return redirect()->route('product.check')
                ->with('success', '新規商品【'.$name.'】の商品種別を更新！残り'.$remain.'件');
        } elseif ($remain == 1) {
            return redirect()->route('sales')
                ->with('success', '新規商品【'.$name.'】の商品種別を更新！');
        } else {
            return redirect()->route('sales')
                ->with('success', '新規商品【'.$name.'】の確認完了！残り件数(remain)の値が不正です。');
        }
    }

    public function delete(IdRequest $request)
    {
        DB::beginTransaction();
        try {
            $result = Product::find($request->validated()['id'])->delete();

            DB::commit();
            return redirect()
                ->route('setting')
                ->with('success', '商品を削除しました。');

        } catch (QueryException $e) {
            DB::rollBack();
            // 外部キー制約違反の場合のエラーメッセージ
            if ($e->errorInfo[1] == 1451) {
                return back()->withErrors(['error' => 'この商品は既に登録されている取引が存在するため削除できません。']);
            }
            // その他のエラー
            \Log::error('商品の削除に失敗: ' . $e->getMessage());
            return back()->withErrors(['error' => '商品の削除に失敗しました。: ' . $e->getMessage()]);
        }
    }
}
