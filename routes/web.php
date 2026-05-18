<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataKunjunganController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SinkronisasiController;

// ─── Root ───
Route::get('/', function () {
    if (session('logged_in')) return redirect()->route('kunjungan.index');
    return redirect()->route('login');
});

// ─── Auth ───
Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Data Kunjungan ───
Route::get('/kunjungan',          [DataKunjunganController::class, 'index'])->name('kunjungan.index');
Route::post('/kunjungan/import',  [DataKunjunganController::class, 'import'])->name('kunjungan.import');
Route::delete('/kunjungan/reset', [DataKunjunganController::class, 'truncate'])->name('kunjungan.truncate');

Route::put('/kunjungan/{id}',    [DataKunjunganController::class, 'update'])->name('kunjungan.update')->where('id', '[0-9]+');
Route::post('/kunjungan/{id}',   [DataKunjunganController::class, 'update'])->where('id', '[0-9]+'); 
Route::delete('/kunjungan/{id}', [DataKunjunganController::class, 'destroy'])->name('kunjungan.destroy')->where('id', '[0-9]+');

Route::get('/sinkronisasi/debug', [SinkronisasiController::class, 'debug']);

Route::prefix('sinkronisasi')->group(function () {

    Route::get('/check', [SinkronisasiController::class, 'check'])
        ->name('sinkronisasi.check');

    Route::get('/preview', [SinkronisasiController::class, 'preview'])
        ->name('sinkronisasi.preview');

    Route::post('/run', [SinkronisasiController::class, 'run'])
        ->name('sinkronisasi.run');

    Route::get('/log', [SinkronisasiController::class, 'log'])
        ->name('sinkronisasi.log');

    Route::get('/foto', [SinkronisasiController::class, 'foto'])
        ->name('sinkronisasi.foto');
    
});