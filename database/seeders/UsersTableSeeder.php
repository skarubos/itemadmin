<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $file = fopen(database_path('seeders\users.csv'), 'r');
        $firstline = true;
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (!$firstline) {
                DB::table('users')->insert([
                    'name' => $data[0],
                    'name_kana' => $data[1],
                    'member_code' => $data[2],
                    'phone_number' => $data[3],
                ]);
            }
            $firstline = false;
        }
        fclose($file);
    }
}

