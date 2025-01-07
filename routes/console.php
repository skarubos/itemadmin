<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\FunctionsController;
use App\Http\Controllers\ScrapingController;
use App\Models\User;
use App\Models\RefreshLog;

Schedule::call(function () {
    DB::beginTransaction();
    try {
        // サービスコンテナを使用してScrapingControllerを解決する
        $controller = app(ScrapingController::class);
        $howMany = $controller->scrape();
        DB::commit();
        RefreshLog::create(['method' => 'scrape', 'caption' => '新規取引の取得', 'status' => 'success', 'error_message' => $howMany]);
    } catch (\Exception $e) {
        DB::rollBack();
        $error_message = explode("\n", $e->getMessage())[0];
        RefreshLog::create(['method' => 'scrape', 'caption' => '新規取引の取得', 'status' => 'failure', 'error_message' => $error_message]);
        \Log::error('新規取引の取得(scrape)に失敗: ' . $e->getMessage());
    }
})->dailyAt('05:55')->name('scraping');

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
})->dailyAt('00:09')->name('refresh_sub');

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