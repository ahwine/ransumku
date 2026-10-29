<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\RationController;
use App\Http\Controllers\StandardController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'masukForm'])->name('masuk');
    Route::post('/masuk', [AuthController::class, 'masuk'])->middleware('throttle:10,1');
    Route::get('/daftar', [AuthController::class, 'daftarForm'])->name('daftar');
    Route::post('/daftar', [AuthController::class, 'daftar']);
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'keluar'])->name('keluar');
    Route::get('/onboarding', [AuthController::class, 'onboardingForm'])->name('onboarding');
    Route::post('/onboarding', [AuthController::class, 'onboarding']);

    Route::middleware('farm')->group(function () {
        Route::get('/', [HomeController::class, 'beranda'])->name('beranda');
        Route::post('/kandang/lokasi', [HomeController::class, 'updateLokasi'])->name('kandang.lokasi');
        Route::get('/tentang', [HomeController::class, 'tentang'])->name('tentang');

        // Modul Manajemen Kandang (Multi-Farm) & Profil Peternak
        Route::get('/kandang', [FarmController::class, 'index'])->name('kandang.index');
        Route::post('/kandang', [FarmController::class, 'store'])->name('kandang.store');
        Route::post('/kandang/switch', [FarmController::class, 'switchFarm'])->name('kandang.switch');
        Route::put('/kandang/{farm}', [FarmController::class, 'update'])->name('kandang.update');
        Route::delete('/kandang/{farm}', [FarmController::class, 'destroy'])->name('kandang.destroy');
        Route::post('/profil', [FarmController::class, 'updateProfile'])->name('profil.update');

        // Modul A: Peracik Ransum
        Route::get('/ransum', [RationController::class, 'index'])->name('ransum.index');
        Route::get('/ransum/baru', [RationController::class, 'create'])->name('ransum.create');
        Route::post('/ransum', [RationController::class, 'store'])->name('ransum.store');
        Route::get('/ransum/{ration}', [RationController::class, 'show'])->name('ransum.show');
        Route::post('/ransum/{ration}/hitung-ulang', [RationController::class, 'hitungUlang'])->name('ransum.hitung-ulang');
        Route::post('/ransum/{ration}/jelaskan', [RationController::class, 'jelaskan'])->name('ransum.jelaskan');
        Route::delete('/ransum/{ration}', [RationController::class, 'destroy'])->name('ransum.destroy');

        Route::resource('bahan', IngredientController::class)->parameters(['bahan' => 'ingredient'])->except('show');
        Route::resource('acuan', StandardController::class)->parameters(['acuan' => 'standard'])->except('show');

        // Modul B: Buku Kandang
        Route::get('/catatan', [LogController::class, 'index'])->name('catatan.index');
        Route::post('/catatan', [LogController::class, 'store'])->name('catatan.store');
        Route::delete('/catatan/{log}', [LogController::class, 'destroy'])->name('catatan.destroy');
        Route::post('/pembelian', [LogController::class, 'simpanPembelian'])->name('pembelian.store');

        // Modul C: Asisten
        Route::get('/asisten', [AssistantController::class, 'index'])->name('asisten.index');
        Route::post('/asisten/chat', [AssistantController::class, 'chat'])->name('asisten.chat')->middleware('throttle:60,1');
        Route::delete('/asisten/chat', [AssistantController::class, 'hapusChat'])->name('asisten.chat.destroy');
        Route::match(['get', 'post'], '/asisten/session/baru', [AssistantController::class, 'baru'])->name('asisten.session.baru');
        Route::post('/asisten/session/{session}/pin', [AssistantController::class, 'pinSession'])->name('asisten.session.pin');
        Route::delete('/asisten/session/{session}', [AssistantController::class, 'hapusSession'])->name('asisten.session.destroy');
        Route::post('/asisten/baca', [AssistantController::class, 'bacaPeringatan'])->name('asisten.baca');
    });
});

// Webhook / API IoT Kandang (ESP32 / DHT22 Sensor Suhu & Kelembaban)
Route::post('/api/kandang/{farm}/sensor', [HomeController::class, 'terimaSensorIot'])
    ->name('api.kandang.sensor')
    ->middleware('throttle:120,1');
