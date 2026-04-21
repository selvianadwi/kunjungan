<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataKunjunganController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/kunjungan', [DataKunjunganController::class, 'index'])->name('kunjungan.index');
Route::post('/kunjungan/import', [DataKunjunganController::class, 'import'])->name('kunjungan.import');
Route::delete('/kunjungan/truncate', [DataKunjunganController::class, 'truncate'])->name('kunjungan.truncate');
Route::delete('/kunjungan/{id}', [DataKunjunganController::class, 'destroy'])->name('kunjungan.destroy');
