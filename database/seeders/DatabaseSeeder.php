<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        // $this->call(UsersTableSeeder::class);
        // $this->call(ProductsTableSeeder::class);
        // $this->call(TradeTypesSeeder::class);
        // $this->call(Depo_RealtimeTableSeeder::class);
        // $this->call(TradingsSeeder::class);
        // $this->call(TradeDetailsSeeder::class);
        $this->call(TradingsIdouSeeder::class);
    }
}