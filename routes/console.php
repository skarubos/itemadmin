<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\FunctionsController;
use App\Models\User;
use App\Models\RefreshLog;

Schedule::call(function () {
    $users = User::where('status', 1)->get();
    DB::beginTransaction();
    try {
        $controller = new FunctionsController();
        $controller->refresh_sub($users);
        DB::commit();
        RefreshLog::create(['method' => 'refresh_sub', 'caption' => '資格手当更新', 'status' => 'success']);
    } catch (\Exception $e) {
        DB::rollBack();
        $error_message = explode("\n", $e->getMessage())[0];
        RefreshLog::create(['method' => 'refresh_sub', 'caption' => '資格手当更新', 'status' => 'failure', 'error_message' => $error_message]);
        \Log::error('自動更新(refresh_sub)に失敗: ' . $e->getMessage());
    }
})->name('refresh_sub');
// ->dailyAt('00:09')

Schedule::call(function () {
    $users = User::where('status', 1)->get();
    DB::beginTransaction();
    try {
        $controller = new FunctionsController();
        $controller->refresh($users);
        DB::commit();
        RefreshLog::create(['method' => 'refresh', 'caption' => '年間実績&最新注文&資格手当の更新', 'status' => 'success']);
    } catch (\Exception $e) {
        DB::rollBack();
        $error_message = explode("\n", $e->getMessage())[0];
        RefreshLog::create(['method' => 'refresh', 'caption' => '年間実績&最新注文&資格手当の更新', 'status' => 'failure', 'error_message' => $error_message]);
        \Log::error('自動更新(refresh)に失敗: ' . $e->getMessage());
    }
})->monthlyOn(1, '00:09')->name('refresh_sales');


// レンタルサーバーのcron設定での記述
// * * * * * cd /home/cf425794/pixelumcraft.com/public_html/itemadmin && /usr/bin/php8.2 artisan schedule:run >> /dev/null 2>&1