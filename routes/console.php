<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\FunctionsController;
use App\Models\User;

Schedule::call(function () {
    $users = User::where('status', 1)->get();
    DB::beginTransaction();
    try {
        $controller = new FunctionsController();
        $controller->refresh_sales($users);
        DB::commit();
        RefreshLog::create(['status' => 'success', 'error_message' => '実績更新(refresh_sales)成功']);
    } catch (\Exception $e) {
        DB::rollBack();
        RefreshLog::create(['status' => 'failure', 'error_message' => '実績更新(refresh_sales)失敗:' . $e->getMessage()]);
        \Log::error('自動更新(refresh_sales)に失敗: ' . $e->getMessage());
    }
})->monthlyOn(1, '01:10')->name('refresh_sales');

Schedule::call(function () {
    $users = User::where('status', 1)->get();
    DB::beginTransaction();
    try {
        $controller = new FunctionsController();
        $controller->refresh_sub($users);
        DB::commit();
        RefreshLog::create(['status' => 'success', 'error_message' => '資格手当更新(refresh_sub)成功']);
    } catch (\Exception $e) {
        DB::rollBack();
        RefreshLog::create(['status' => 'failure', 'error_message' => '資格手当更新(refresh_sub)失敗:' . $e->getMessage()]);
        \Log::error('自動更新(refresh_sub)に失敗: ' . $e->getMessage());
    }
})->dailyAt('00:10')->name('refresh_sub');

// レンタルサーバーのcron設定での記述
// * * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

// Artisan::command('refresh_sales', function () {
//     DB::beginTransaction();
//     try {
//         $controller = new FunctionsController();
//         $controller->refresh_sales();
//         DB::commit();
//     } catch (\Exception $e) {
//         DB::rollBack();
//     }
// })->describe('usersテーブルのsalesカラムを更新');

// Artisan::command('refresh_sub', function () {
//     $controller = new FunctionsController();
//     $controller->refresh_sub();
// })->describe('usersテーブルのsub_nowカラムを更新');

// $schedule->command('refresh_sales')->monthlyOn(1, '05:10');
// $schedule->command('refresh_sub')->dailyAt('05:10');