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

    // depo_home, sales_home, refresh_salesはpermissionが1以下ならアクセス可能
    Route::middleware('checkPermission:1')->group(function () {
        Route::get('/depo', [HomeController::class, 'depo_home'])->name('depo_home');
        Route::get('/sales', [HomeController::class, 'sales_home'])->name('sales_home');
        Route::get('/refresh_sales', [PostingController::class, 'refresh_sales'])->name('refresh_sales');
        Route::get('/upload', [HomeController::class, 'upload'])->name('upload');
        Route::post('/upload_check', [PostingController::class, 'upload_check'])->name('upload_check');
        Route::post('/save', [PostingController::class, 'save'])->name('save');
        Route::match(['get', 'post'], '/admin', [HomeController::class, 'admin'])->name('admin');
        Route::post('/delete', [PostingController::class, 'delete'])->name('delete');
    });

    // depo_detail, depo_detail_history, sales_detailは、permission=1なら誰のデータでも閲覧可能
    // permission=2の場合は、自分のデータのみ閲覧可能
    Route::middleware('checkPermission:2')->group(function () {
        Route::get('/depo_detail/{member_code}', [HomeController::class, 'depo_detail'])->name('depo_detail');
        Route::get('/depo_detail_history/{member_code}', [HomeController::class, 'depo_detail_history'])->name('depo_detail_history');
        Route::get('/sales_detail/{member_code}', [HomeController::class, 'sales_detail'])->name('sales_detail');
    });
});


require __DIR__.'/auth.php';


