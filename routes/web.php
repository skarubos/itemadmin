<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FunctionsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ScrapingController;
use App\Http\Controllers\TradingController;
use App\Http\Controllers\TradeTypeController;
use App\Http\Controllers\ProductController;


Route::get('/', function () {
    // ログインしている場合、ダッシュボードへリダイレクト
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // permissionが1ならアクセス可能
    Route::middleware('checkPermission:1')->group(function () {
        Route::get('/sales', [HomeController::class, 'show_sales_home'])->name('sales');
        Route::get('/depo', [HomeController::class, 'show_depo_home'])->name('depo');
        Route::get('/upload', [HomeController::class, 'show_upload'])->name('trade.upload');
        Route::get('/summary', [HomeController::class, 'show_monthly_summary'])->name('summary');

        Route::get('/admin', [HomeController::class, 'show_admin'])->name('admin');
        Route::post('/show_dashboard', [HomeController::class, 'show_dashboard'])->name('show_dashboard');
        Route::post('/refresh_member', [HomeController::class, 'refresh_member'])->name('refresh_member');
        Route::get('/refresh_all', [HomeController::class, 'refresh_all'])->name('refresh_all');
        Route::get('/reset_all', [HomeController::class, 'reset_all'])->name('reset_all');
        
        Route::get('/trade/create', [TradingController::class, 'show_create'])->name('trade.create');
        Route::get('/trade/create/idou', [TradingController::class, 'show_create_idou'])->name('trade.create.idou');
        Route::post('/trade/create/upload', [TradingController::class, 'upload_check'])->name('trade.create.upload');
        Route::get('/trade/check', [TradingController::class, 'show_check'])->name('trade.check');
        Route::get('/trade/checked/{trade_id}/{remain}', [TradingController::class, 'change_status']);
        Route::get('/trade/edit/{trade_id}/{remain}', [TradingController::class, 'show_edit']);
        Route::get('/trade/edit', [TradingController::class, 'show_edit_request'])->name('trade.edit');
        Route::post('/trade/edit', [TradingController::class, 'store']);
        Route::post('/trade/delete', [TradingController::class, 'delete'])->name('trade.delete');

        Route::get('/scraping', [ScrapingController::class, 'testScrape'])->name('scraping');


        Route::get('/setting', [HomeController::class, 'show_setting'])->name('setting');

        Route::get('/tradeType/create', [TradeTypeController::class, 'show_create'])->name('setting.tradeType.create');
        Route::post('/tradeType/create', [TradeTypeController::class, 'store']);
        Route::get('/tradeType/edit', [TradeTypeController::class, 'show_edit'])->name('setting.tradeType.edit');
        Route::post('/tradeType/edit', [TradeTypeController::class, 'store']);
        Route::post('/tradeType/delete', [TradeTypeController::class, 'delete'])->name('setting.tradeType.delete');
        
        Route::get('/product/create', [ProductController::class, 'show_create'])->name('setting.product.create');
        Route::get('/product/edit', [ProductController::class, 'show_edit'])->name('setting.product.edit');
        Route::post('/product/edit', [ProductController::class, 'update']);
        Route::post('/product/delete', [ProductController::class, 'delete'])->name('setting.product.delete');
        Route::get('/product/check', [ProductController::class, 'show_product_check'])->name('product.check');

        Route::get('/user/create', [ProductController::class, 'show_create'])->name('setting.user.create');
        Route::get('/user/edit', [ProductController::class, 'show_edit'])->name('setting.user.edit');
        Route::post('/user/edit', [ProductController::class, 'update_edit']);

        Route::get('/test', [HomeController::class, 'test'])->name('test');
    });

    // permission=1なら誰のデータでも閲覧可能
    // permission=2の場合は、自分のデータのみ閲覧可能
    Route::middleware('checkPermission:2')->group(function () {
        Route::get('/sales/member/{member_code}', [HomeController::class, 'show_member_sales'])->name('sales.member');
        Route::get('/sales/member/{member_code}/list', [HomeController::class, 'show_member_sales_list'])->name('sales.list');
        Route::get('/depo/member/{member_code}', [HomeController::class, 'show_member_depo'])->name('depo.member');
        Route::get('/depo/member/{member_code}/history', [HomeController::class, 'show_member_depo_history'])->name('depo.history');
        Route::get('/sub/{member_code}', [HomeController::class, 'show_member_sub'])->name('sub.member');
        Route::get('/trade/{member_code}/{trade_id}', [HomeController::class, 'show_trade'])->name('trade.detail');
    });
});


require __DIR__.'/auth.php';


