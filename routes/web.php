<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\DepoController;


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
    Route::get('/depo', [DepoController::class, 'depo'])->name('depo');
    Route::get('/depo_detail/{member_code}', [DepoController::class, 'detail'])->name('detail');
});

Route::post('/import', [ExcelImportController::class, 'import']);

require __DIR__.'/auth.php';
