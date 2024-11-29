<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TradeDetailsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CSVファイルのパス
        $csvFile = database_path('seeders\trade_details.csv');

        // CSVファイルの読み込み
        $csv = array_map('str_getcsv', file($csvFile));

        // ヘッダー行を除去
        array_shift($csv);

        foreach ($csv as $row) {
            $tradingId = $row[0];
            $productName = $row[1];
            $amount = $row[2];

            // productsテーブルからproduct_idを取得
            $productId = DB::table('products')->where('name', $productName)->value('id');

            // データをtrade_detailsテーブルに挿入
            DB::table('trade_details')->insert([
                'trading_id' => $tradingId,
                'product_id' => $productId,
                'amount' => $amount,
            ]);
        }
    }
}
