<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class Depo_RealtimeTableSeeder extends Seeder
{
    public function run(): void
    {
        $file = fopen(database_path('seeders\depo_realtime.csv'), 'r');
        $firstline = true;
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (!$firstline) {
                DB::table('depo_realtime')->insert([
                    'member_code' => $data[0],
                    'product_id' => $data[1],
                    'amount' => $data[2],
                ]);
            }
            $firstline = false;
        }
        fclose($file);
    }
}
