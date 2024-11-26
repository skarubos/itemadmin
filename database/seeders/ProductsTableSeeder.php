<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductsTableSeeder extends Seeder
{
    public function run()
    {
        $file = fopen(database_path('seeders\products.csv'), 'r');
        $firstline = true;
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (!$firstline) {
                DB::table('products')->insert([
                    'id' => $data[0],
                    'name' => $data[1],
                    'product_type' => $data[2],
                ]);
            }
            $firstline = false;
        }
        fclose($file);
    }
}

