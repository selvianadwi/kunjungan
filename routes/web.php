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
Route::put('/kunjungan/{id}',     [DataKunjunganController::class, 'update'])->name('kunjungan.update');
Route::delete('/kunjungan/{id}',  [DataKunjunganController::class, 'destroy'])->name('kunjungan.destroy');
Route::post('/kunjungan/{id}',    [DataKunjunganController::class, 'update']); // fallback _method=PUT

// ─── Sinkronisasi ───
// Route::get('sinkronisasi/check',   [SinkronisasiController::class, 'checkConnection'])->name('sinkronisasi.check');
// Route::post('sinkronisasi/run',    [SinkronisasiController::class, 'sync'])->name('sinkronisasi.run');
// Route::get('sinkronisasi/preview', [SinkronisasiController::class, 'preview'])->name('sinkronisasi.preview');
// Route::get('sinkronisasi/log',     [SinkronisasiController::class, 'getLog'])->name('sinkronisasi.log');      // ← getLog, bukan getLogHistory
// Route::get('sinkronisasi/foto',    [SinkronisasiController::class, 'foto'])->name('sinkronisasi.foto');       // ← foto, bukan proxyFoto


// routes/web.php atau api.php — hapus setelah selesai debug
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