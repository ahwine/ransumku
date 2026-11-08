<?php

use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FarmController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\RationController;
use App\Http\Controllers\ReportController;
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
        Route::get('/kontroler', [HomeController::class, 'kontroler'])->name('kontroler.index');
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
        Route::post('/ransum/{ration}/terapkan', [RationController::class, 'terapkan'])->name('ransum.terapkan');
        Route::post('/ransum/{ration}/batal-terapkan', [RationController::class, 'batalTerapkan'])->name('ransum.batal-terapkan');
        Route::delete('/ransum/{ration}', [RationController::class, 'destroy'])->name('ransum.destroy');

        Route::post('/bahan/stok/update', [IngredientController::class, 'updateStok'])->name('bahan.stok.update');
        Route::delete('/bahan/gudang/{farmIngredient}', [IngredientController::class, 'hapusDariGudang'])->name('bahan.gudang.destroy');
        Route::resource('bahan', IngredientController::class)->parameters(['bahan' => 'ingredient'])->except('show');
        Route::post('/acuan/sync', [StandardController::class, 'sync'])->name('acuan.sync');
        Route::post('/acuan/terapkan-preset', [StandardController::class, 'terapkanPreset'])->name('acuan.terapkan-preset');
        Route::resource('acuan', StandardController::class)->parameters(['acuan' => 'standard'])->except('show');

        // Modul B: Buku Kandang
        Route::get('/catatan', [LogController::class, 'index'])->name('catatan.index');
        Route::post('/catatan', [LogController::class, 'store'])->name('catatan.store');
        Route::delete('/catatan/{log}', [LogController::class, 'destroy'])->name('catatan.destroy');
        Route::post('/pembelian', [LogController::class, 'simpanPembelian'])->name('pembelian.store');

        // Modul Laporan Sistem Terpadu
        Route::get('/laporan', [ReportController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/unduh-pdf', [ReportController::class, 'unduhPdf'])->name('laporan.pdf');
        Route::get('/laporan/unduh-excel', [ReportController::class, 'unduhExcel'])->name('laporan.excel');
        Route::get('/laporan/unduh-csv', [ReportController::class, 'unduhCsv'])->name('laporan.csv');

        // Modul C: Asisten
        Route::get('/asisten', [AssistantController::class, 'index'])->name('asisten.index');
        Route::get('/asisten/status', [AssistantController::class, 'status'])->name('asisten.status');
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
Route::post('/api/kandang/{farm}/sensor/putus', [HomeController::class, 'putusSensorIot'])
    ->name('api.kandang.sensor.putus');
Route::get('/api/kandang/{farm}/sensor', [HomeController::class, 'statusSensorLive'])
    ->name('api.kandang.sensor.live');
Route::get('/api/kandang/{farm}/bmkg', [HomeController::class, 'acuanBmkgLive'])
    ->name('api.kandang.bmkg');

// API Kontroler Aktuator Pintar Kandang (Hardware-Agnostic Controller)
Route::post('/api/kandang/{farm}/kontrol/toggle-siap', [HomeController::class, 'toggleControllerSiap'])
    ->name('api.kandang.kontrol.toggle-siap');
Route::post('/api/kandang/{farm}/kontrol/mode', [HomeController::class, 'ubahModeKontroler'])
    ->name('api.kandang.kontrol.mode');
Route::post('/api/kandang/{farm}/kontrol/sakelar', [HomeController::class, 'ubahSakelarAktuator'])
    ->name('api.kandang.kontrol.sakelar');
Route::post('/api/kandang/{farm}/kontrol/settings', [HomeController::class, 'simpanSettingKontroler'])
    ->name('api.kandang.kontrol.settings');
