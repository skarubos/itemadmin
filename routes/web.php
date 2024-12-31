<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FunctionsController;
use App\Http\Controllers\PostingController;
use App\Http\Controllers\HomeController;


Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // depo_home, sales_home, refresh_salesはpermissionが1ならアクセス可能
    Route::middleware('checkPermission:1')->group(function () {
        Route::get('/depo', [HomeController::class, 'depo_home'])->name('depo');
        Route::get('/sales', [HomeController::class, 'sales_home'])->name('sales');
        Route::post('/refresh_member', [HomeController::class, 'refresh_member'])->name('refresh_member');
        Route::get('/refresh_all', [HomeController::class, 'refresh_all'])->name('refresh_all');
        Route::get('/upload', [HomeController::class, 'upload'])->name('upload');
        Route::post('/upload_check', [PostingController::class, 'upload_check'])->name('upload_check');
        Route::post('/trade/save', [PostingController::class, 'save_trade'])->name('trade.save');
        Route::match(['get', 'post'], '/admin', [HomeController::class, 'admin'])->name('admin');
        Route::get('/trade/edit', [PostingController::class, 'show_edit_trade'])->name('trade.edit');
        Route::post('/delete', [PostingController::class, 'delete'])->name('delete');
        Route::get('/reset_all', [HomeController::class, 'reset_all'])->name('reset_all');
        Route::get('/test', [HomeController::class, 'test'])->name('test');
        Route::post('/show_dashboard', [HomeController::class, 'show_dashboard'])->name('show_dashboard');
    });

    // permission=1なら誰のデータでも閲覧可能
    // permission=2の場合は、自分のデータのみ閲覧可能
    Route::middleware('checkPermission:2')->group(function () {
        Route::get('/depo/member/{member_code}', [HomeController::class, 'depo_detail'])->name('depo.member');
        Route::get('/depo/member/{member_code}/history', [HomeController::class, 'depo_detail_history'])->name('depo.history');
        Route::get('/sales/member/{member_code}', [HomeController::class, 'sales_detail'])->name('sales.member');
        Route::get('/sales/member/{member_code}/list', [HomeController::class, 'sales_list'])->name('sales.list');
        Route::get('/sub/{member_code}', [HomeController::class, 'sub_detail'])->name('sub.detail');
        Route::get('/trade/{member_code}/{trade_id}', [HomeController::class, 'trade_detail'])->name('trade.detail');
    });
});


require __DIR__.'/auth.php';


