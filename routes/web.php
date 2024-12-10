<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FunctionsController;
use App\Http\Controllers\PostingController;
use App\Http\Controllers\HomeController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/upload', function () {
    return view('upload');
})->middleware(['auth', 'verified'])->name('upload');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/depo', [HomeController::class, 'depo_home'])->name('depo_home');
    Route::get('/depo_detail/{member_code}', [HomeController::class, 'depo_detail'])->name('depo_detail');
    Route::get('/sales', [HomeController::class, 'sales_home'])->name('sales_home');
    Route::get('/refresh_sales', [PostingController::class, 'refresh_sales'])->name('refresh_sales');
    Route::get('/sales_detail/{member_code}', [HomeController::class, 'sales_detail'])->name('sales_detail');
    Route::post('/upload_check', [PostingController::class, 'upload_check'])->name('upload_check');
    Route::post('/save', [PostingController::class, 'save'])->name('save');
    Route::match(['get', 'post'], '/admin', [HomeController::class, 'admin'])->name('admin');
    Route::post('/delete', [PostingController::class, 'delete'])->name('delete');
});

require __DIR__.'/auth.php';
