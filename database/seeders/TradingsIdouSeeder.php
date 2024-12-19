<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class TradingsIdouSeeder extends Seeder
{
    public function run()
    {
        $file = fopen(database_path('seeders\tradings_idou.csv'), 'r');
        $firstline = true;
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (!$firstline) {
                DB::table('tradings')->insert([
                    'id' => $data[0],
                    'member_code' => $data[1],
                    'date' => $data[2],
                    'trade_type' => $data[3],
                    'amount' => $data[4],
                ]);
            }
            $firstline = false;
        }
        fclose($file);
    }
}