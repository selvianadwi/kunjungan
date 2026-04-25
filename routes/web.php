<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataKunjunganController;
use App\Http\Controllers\AuthController;

// ─── Root: redirect ke login jika belum login ───
Route::get('/', function () {
    if (session('logged_in')) {
        return redirect()->route('kunjungan.index');
    }
    return redirect()->route('login');
});

// ─── Login & Logout ───
Route::get('/login',   [AuthController::class, 'showLogin'])->name('login');
Route::post('/login',  [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ─── Data Kunjungan ───
// Pengecekan session (checkAuth) dilakukan di dalam controller masing-masing
Route::get('/kunjungan',          [DataKunjunganController::class, 'index'])->name('kunjungan.index');
Route::post('/kunjungan/import',  [DataKunjunganController::class, 'import'])->name('kunjungan.import');
Route::delete('/kunjungan/reset', [DataKunjunganController::class, 'truncate'])->name('kunjungan.truncate');
Route::put('/kunjungan/{id}',     [DataKunjunganController::class, 'update'])->name('kunjungan.update');
Route::delete('/kunjungan/{id}',  [DataKunjunganController::class, 'destroy'])->name('kunjungan.destroy');
