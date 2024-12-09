<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TradeTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CSVファイルのパス
        $csvFile = database_path('seeders\trade_types.csv');

        // CSVファイルの読み込み
        $csv = array_map('str_getcsv', file($csvFile));

        // ヘッダー行を除去
        array_shift($csv);

        foreach ($csv as $row) {
            $tradeType = $row[0];
            $tradeName = $row[1];
            $caption = $row[2];

            // データをtrade_typesテーブルに挿入
            DB::table('trade_types')->insert([
                'trade_type' => $tradeType,
                'name' => $tradeName,
                'caption' => $caption,
            ]);
        }
    }
}
