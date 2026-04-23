<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataKunjunganController;

Route::get('/', function () {
    return view('welcome');
});


Route::put('/kunjungan/{id}', [DataKunjunganController::class, 'update'])->name('kunjungan.update');
Route::get('/', [DataKunjunganController::class, 'index'])->name('kunjungan.index');
Route::post('/import', [DataKunjunganController::class, 'import'])->name('kunjungan.import');
Route::delete('/{id}', [DataKunjunganController::class, 'destroy'])->name('kunjungan.destroy');
Route::put('/{id}', [DataKunjunganController::class, 'update'])->name('kunjungan.update');
