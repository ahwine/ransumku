<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function beranda(WeatherService $weatherService)
    {
        $farm = $this->farm();
        $weather = $weatherService->getWeather($farm);
        $presetKota = WeatherService::PRESET_KOTA;

        $logs = $farm->logs()->orderByDesc('tanggal')->limit(7)->get()->reverse()->values();
        $hariIni = $farm->logs()->orderByDesc('tanggal')->first();
        $ration = $farm->lastRation();

        // Biaya pakan per kg telur (estimasi) pada catatan terakhir
        $biayaKgTelur = null;
        if ($hariIni && $ration && $hariIni->telurKgEfektif() > 0) {
            $biayaKgTelur = $hariIni->pakan_kg * $ration->biaya_per_kg / $hariIni->telurKgEfektif();
        }

        // Kalkulasi Keuangan & Margin Laba Kotor Telur (Acuan Pasar Layer)
        $hargaTelurKg = (int) config('ransumku.harga_telur_default', 26000);
        $estPendapatanTelur = $hariIni ? (int) round($hariIni->telurKgEfektif() * $hargaTelurKg) : 0;
        $biayaPakanHariIni = ($hariIni && $ration) ? (int) round($hariIni->pakan_kg * $ration->biaya_per_kg) : (int) round(($hariIni?->pakan_kg ?? 0) * 6850);
        $estMarginKotor = $estPendapatanTelur > 0 ? ($estPendapatanTelur - $biayaPakanHariIni) : null;
        $estMarginPerKgTelur = ($biayaKgTelur !== null) ? round($hargaTelurKg - $biayaKgTelur) : null;

        $alerts = $farm->alerts()->where('tanggal_data', '>=', now()->subDays(7))->latest('tanggal_data')->latest('id')->get();
        if ($alerts->isEmpty()) {
            $alerts = $farm->alerts()->latest('tanggal_data')->latest('id')->limit(5)->get();
        }
        $terlama = $farm->hargaTerlamaDiperbarui();
        $hargaUsangHari = $terlama && $terlama->lt(now()->subDays(config('ransumku.harga_usang_hari')))
            ? (int) $terlama->diffInDays(now()) : null;

        // Metrik Kuadran 1: Produksi Telur
        $totalTelurIni = (int) ($hariIni?->telur_butir ?? 0);
        $totalTelurKgIni = (float) ($hariIni ? $hariIni->telurKgEfektif() : 0);
        $rataGrTelur = ($totalTelurIni > 0 && $totalTelurKgIni > 0)
            ? round(($totalTelurKgIni * 1000) / $totalTelurIni, 1)
            : 62.0;
        $hdPersen = ($farm->jumlah_ayam > 0 && $totalTelurIni > 0)
            ? round(($totalTelurIni / $farm->jumlah_ayam) * 100, 1)
            : 0;
        $telurUtuh = $totalTelurIni > 0 ? (int) round($totalTelurIni * 0.995) : 0;
        $telurBentes = $totalTelurIni > 0 ? (int) ($totalTelurIni - $telurUtuh) : 0;

        $kemarinLog = $logs->count() >= 2 ? $logs[$logs->count() - 2] : null;
        $deltaTelur = null;
        if ($kemarinLog && $kemarinLog->telur_butir > 0) {
            $deltaTelur = round((($totalTelurIni - $kemarinLog->telur_butir) / $kemarinLog->telur_butir) * 100, 1);
        }

        // Metrik Kuadran 2: Populasi Ayam
        $totalMatiIni = (int) ($hariIni?->ayam_mati ?? 0);

        // Metrik Kuadran 3: Pakan
        $standarPakanGr = (int) round(config('ransumku.konsumsi_default', 0.11) * 1000);
        $totalPakanIni = (float) ($hariIni?->pakan_kg ?? 0);
        $gramPerEkor = ($farm->jumlah_ayam > 0 && $totalPakanIni > 0)
            ? round(($totalPakanIni * 1000) / $farm->jumlah_ayam, 1)
            : 0;

        // Metrik Kuadran 4: Ratio & Efisiensi
        $eggMass = ($hdPersen > 0 && $rataGrTelur > 0)
            ? round(($hdPersen * $rataGrTelur) / 100, 1)
            : 0;
        $fcr = ($totalTelurKgIni > 0 && $totalPakanIni > 0)
            ? round($totalPakanIni / $totalTelurKgIni, 2)
            : 0;

        // Data Grafik (Chart.js)
        $chartLabels = $logs->isNotEmpty()
            ? $logs->map(fn ($l) => $l->tanggal->format('d/m'))->all()
            : ['H-6', 'H-5', 'H-4', 'H-3', 'H-2', 'Kemarin', 'Hari Ini'];

        $chartTelur = [
            'type' => 'bar',
            'labels' => $chartLabels,
            'datasets' => [
                [
                    'label' => 'Telur (butir)',
                    'data' => $logs->isNotEmpty() ? $logs->pluck('telur_butir')->all() : [0, 0, 0, 0, 0, 0, 0],
                    'backgroundColor' => '#15803d',
                    'hoverBackgroundColor' => '#166534',
                    'borderRadius' => 0,
                    'maxBarThickness' => 38,
                ],
            ],
        ];

        $pakanGrData = $logs->isNotEmpty()
            ? $logs->map(fn ($l) => $farm->jumlah_ayam > 0 ? round(($l->pakan_kg * 1000) / $farm->jumlah_ayam, 1) : 0)->all()
            : [0, 0, 0, 0, 0, 0, 0];

        $chartPakan = [
            'type' => 'line',
            'labels' => $chartLabels,
            'legend' => false,
            'datasets' => [
                [
                    'label' => 'Konsumsi (gr/ekor)',
                    'data' => $pakanGrData,
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => '#F59E0B18',
                    'fill' => true,
                    'tension' => 0.35,
                    'borderWidth' => 2.5,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#FFFFFF',
                    'pointBorderColor' => '#F59E0B',
                    'pointBorderWidth' => 2,
                ],
                [
                    'label' => 'Standar (gr)',
                    'data' => array_fill(0, count($chartLabels), $standarPakanGr),
                    'borderColor' => '#9CA3AF',
                    'borderDash' => [4, 4],
                    'fill' => false,
                    'tension' => 0,
                    'borderWidth' => 1.5,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 0,
                ],
            ],
        ];

        $populasiData = [];
        if ($logs->isNotEmpty()) {
            foreach ($logs as $l) {
                $populasiData[] = max(0, $farm->jumlah_ayam - ($l->ayam_mati ?? 0));
            }
        } else {
            $populasiData = array_fill(0, count($chartLabels), $farm->jumlah_ayam);
        }

        $chartPopulasi = [
            'type' => 'line',
            'labels' => $chartLabels,
            'zero' => false,
            'precision' => 0,
            'datasets' => [
                [
                    'label' => 'Populasi (ekor)',
                    'data' => $populasiData,
                    'borderColor' => '#2563EB',
                    'backgroundColor' => '#2563EB12',
                    'fill' => true,
                    'tension' => 0.35,
                    'borderWidth' => 2.5,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#FFFFFF',
                    'pointBorderColor' => '#2563EB',
                    'pointBorderWidth' => 2,
                ],
            ],
        ];

        $fcrData = [];
        if ($logs->isNotEmpty()) {
            foreach ($logs as $l) {
                $kgTelur = $l->telurKgEfektif();
                $fcrData[] = ($kgTelur > 0 && $l->pakan_kg > 0)
                    ? round($l->pakan_kg / $kgTelur, 2)
                    : 2.15;
            }
        } else {
            $fcrData = array_fill(0, count($chartLabels), 2.15);
        }

        $chartFcr = [
            'type' => 'line',
            'labels' => $chartLabels,
            'legend' => false,
            'zero' => false,
            'datasets' => [
                [
                    'label' => 'FCR Aktual',
                    'data' => $fcrData,
                    'borderColor' => '#7C3AED',
                    'backgroundColor' => '#7C3AED12',
                    'fill' => true,
                    'tension' => 0.35,
                    'borderWidth' => 2.5,
                    'pointRadius' => 4,
                    'pointHoverRadius' => 6,
                    'pointBackgroundColor' => '#FFFFFF',
                    'pointBorderColor' => '#7C3AED',
                    'pointBorderWidth' => 2,
                ],
                [
                    'label' => 'Standar SNI (2.15)',
                    'data' => array_fill(0, count($chartLabels), 2.15),
                    'borderColor' => '#9CA3AF',
                    'borderDash' => [4, 4],
                    'fill' => false,
                    'tension' => 0,
                    'borderWidth' => 1.5,
                    'pointRadius' => 0,
                    'pointHoverRadius' => 0,
                ],
            ],
        ];

        return view('beranda', compact(
            'farm', 'logs', 'hariIni', 'ration', 'biayaKgTelur', 'alerts', 'hargaUsangHari',
            'totalTelurIni', 'totalTelurKgIni', 'rataGrTelur', 'hdPersen', 'telurUtuh', 'telurBentes',
            'deltaTelur', 'standarPakanGr', 'totalPakanIni', 'gramPerEkor', 'totalMatiIni',
            'eggMass', 'fcr', 'chartTelur', 'chartPakan', 'chartPopulasi', 'chartFcr',
            'weather', 'presetKota',
            'hargaTelurKg', 'estPendapatanTelur', 'biayaPakanHariIni', 'estMarginKotor', 'estMarginPerKgTelur'
        ));
    }

    /**
     * Memperbarui lokasi sentra kandang untuk sinkronisasi cuaca mikro Open-Meteo.
     */
    public function updateLokasi(Request $request): RedirectResponse
    {
        $farm = $this->farm();

        $data = $request->validate([
            'kota' => 'required|string|max:50',
        ]);

        $kota = $data['kota'];
        $preset = WeatherService::PRESET_KOTA[$kota] ?? null;

        if ($preset) {
            $farm->update([
                'lokasi_kota' => $kota,
                'latitude' => $preset['lat'],
                'longitude' => $preset['lon'],
            ]);
        } else {
            $farm->update([
                'lokasi_kota' => $kota,
            ]);
        }

        // Bersihkan cache cuaca lama
        Cache::forget("weather_farm_{$farm->id}_{$kota}");

        return back()->with('ok', "Lokasi cuaca kandang berhasil diperbarui ke {$farm->lokasi_kota}.");
    }

    /**
     * Endpoint API Webhook IoT (ESP32 / DHT22 / DHT11) untuk menerima data suhu fisik kandang.
     */
    public function terimaSensorIot(Request $request, Farm $farm, WeatherService $weatherService): JsonResponse
    {
        $raw = $request->json()->all();
        if (empty($raw)) {
            $raw = $request->all();
        }
        if (isset($raw['kelembapan']) && !isset($raw['kelembaban'])) {
            $raw['kelembaban'] = $raw['kelembapan'];
        }
        if (!empty($raw)) {
            $request->merge($raw);
        }

        $data = $request->validate([
            'suhu' => 'required|numeric|between:10,55',
            'kelembaban' => 'required|numeric|between:10,100',
            'token' => 'nullable|string',
        ]);

        if ($farm->iot_token && $request->input('token') !== $farm->iot_token) {
            return response()->json([
                'ok' => false,
                'pesan' => 'Token otentikasi IoT tidak valid untuk kandang ini.',
            ], 403);
        }

        $farm->update([
            'sensor_suhu' => $data['suhu'],
            'sensor_kelembaban' => $data['kelembaban'],
            'sensor_updated_at' => now(),
        ]);

        Cache::forget("weather_farm_{$farm->id}_{$farm->lokasi_kota}");
        $w = $weatherService->getWeather($farm);
        $kontrol = $this->evaluasiLogikaKontroler($farm, (float) $w['suhu'], (float) $w['kelembaban'], (float) $w['thi']);
        $kontrol['siap'] = (bool) $farm->controller_siap;

        return response()->json([
            'ok' => true,
            'pesan' => 'Data sensor IoT suhu & kelembaban kandang berhasil dicatat.',
            'suhu' => $w['suhu'],
            'kelembaban' => $w['kelembaban'],
            'thi' => $w['thi'],
            'status' => $w['thi_kategori'],
            'level' => $w['thi_level'],
            'saran_manajemen' => $w['saran_manajemen'],
            'kontrol' => $kontrol,
        ]);
    }

    /**
     * Endpoint live polling status cuaca & sensor kandang terkini secara real-time.
     */
    public function statusSensorLive(Farm $farm, WeatherService $weatherService): JsonResponse
    {
        $isOnline = $weatherService->isSensorOnline($farm, 60);

        // Gunakan getWeather yang sudah mengutamakan sensor online atau cache cuaca Open-Meteo/BMKG
        // sehingga tidak memblokir server single-threaded PHP built-in dengan request HTTP outbound terus-menerus
        $w = $weatherService->getWeather($farm);

        $kontrol = $this->evaluasiLogikaKontroler($farm, (float) $w['suhu'], (float) $w['kelembaban'], (float) $w['thi']);

        return response()->json([
            'ok' => true,
            'suhu' => (float) $w['suhu'],
            'kelembaban' => (float) $w['kelembaban'],
            'apparent_temp' => (float) $w['apparent_temp'],
            'angin_kmh' => (float) $w['angin_kmh'],
            'cuaca_teks' => $w['cuaca_teks'],
            'weather_type' => $w['weather_type'] ?? 'partly_cloudy',
            'sumber' => $w['sumber'],
            'kota' => $w['kota'] ?? ($farm->lokasi_kota ?: 'Kandang'),
            'thi' => (float) $w['thi'],
            'thi_kategori' => $w['thi_kategori'],
            'thi_kategori_singkat' => $w['thi_kategori_singkat'],
            'thi_percent' => (float) ($w['thi_percent'] ?? 50.0),
            'thi_level' => $w['thi_level'],
            'badge_class' => $w['badge_class'],
            'dampak_ayam' => $w['dampak_ayam'] ?? ($w['deskripsi_risiko'] ?? 'Kondisi fisiologi ayam terpantau aman.'),
            'saran_manajemen' => $w['saran_manajemen'] ?? [],
            'updated_at' => $w['updated_at'],
            'sensor_aktif' => $isOnline,
            'sensor_online' => $isOnline,
            'detik_lalu' => $farm->sensor_updated_at ? Carbon::parse($farm->sensor_updated_at)->diffInSeconds(now()) : null,
            'kontrol' => $kontrol,
        ]);
    }

    /**
     * Mengubah status kesiapan sistem kontroler kandang (Merah Standby <-> Biru Siap).
     */
    public function toggleControllerSiap(Request $request, Farm $farm, WeatherService $weatherService): JsonResponse
    {
        $statusBaru = ! (bool) $farm->controller_siap;
        $farm->update([
            'controller_siap' => $statusBaru,
        ]);

        $w = $weatherService->getWeather($farm);
        $kontrol = $this->evaluasiLogikaKontroler($farm, (float) $w['suhu'], (float) $w['kelembaban'], (float) $w['thi']);
        $kontrol['siap'] = (bool) $farm->controller_siap;

        $pesan = $statusBaru
            ? 'Sistem kontroler berhasil DIAKTIFKAN (Status BIRU). Aktuator kandang siap mengeksekusi kendali mikroklimat!'
            : 'Sistem kontroler DINONAKTIFKAN (Status MERAH). Seluruh aktuator kandang kembali ke mode siaga.';

        return response()->json([
            'ok' => true,
            'pesan' => $pesan,
            'kontrol' => $kontrol,
        ]);
    }

    /**
     * Mengubah mode operasi kontroler (otomatis <-> manual).
     */
    public function ubahModeKontroler(Request $request, Farm $farm, WeatherService $weatherService): JsonResponse
    {
        $data = $request->validate([
            'mode' => 'required|in:otomatis,manual',
        ]);

        $farm->update([
            'controller_mode' => $data['mode'],
            'controller_siap' => true, // Otomatis siap saat mode diatur
        ]);

        $w = $weatherService->getWeather($farm);
        $kontrol = $this->evaluasiLogikaKontroler($farm, (float) $w['suhu'], (float) $w['kelembaban'], (float) $w['thi']);
        $kontrol['siap'] = (bool) $farm->controller_siap;

        $pesan = $data['mode'] === 'otomatis'
            ? 'Mode Kontrol Otomatis diaktifkan. Seluruh aktuator kini dikendalikan penuh secara cerdas oleh AI & biometeorologi THI.'
            : 'Mode Kontrol Manual diaktifkan. Anda kini dapat mengatur sakelar setiap aktuator secara mandiri.';

        return response()->json([
            'ok' => true,
            'pesan' => $pesan,
            'kontrol' => $kontrol,
        ]);
    }

    /**
     * Mengatur sakelar aktuator fisik secara langsung per controller (Override Manual).
     */
    public function ubahSakelarAktuator(Request $request, Farm $farm): JsonResponse
    {
        $data = $request->validate([
            'alat' => 'required|in:kipas,misting,lampu,pakan',
            'status' => 'required|boolean',
        ]);

        $kolom = 'relay_' . $data['alat'];
        $farm->update([
            $kolom => (bool) $data['status'],
        ]);
        $farm->refresh();
        $adaAksi = (bool) ($farm->relay_kipas || $farm->relay_misting || $farm->relay_lampu || $farm->relay_pakan);
        $farm->update([
            'controller_siap' => $adaAksi,
        ]);

        return response()->json([
            'ok' => true,
            'pesan' => ucfirst($data['alat']) . ' berhasil di-' . ($data['status'] ? 'nyalakan' : 'matikan') . '.',
            'kontrol' => [
                'siap' => $adaAksi,
                'relay_kipas' => (bool) $farm->relay_kipas,
                'relay_misting' => (bool) $farm->relay_misting,
                'relay_lampu' => (bool) $farm->relay_lampu,
                'relay_pakan' => (bool) $farm->relay_pakan,
                'settings' => $farm->getSettingsKontroler(),
            ],
        ]);
    }

    /**
     * Menyimpan pengaturan otomatisasi kapan alat nyala otomatis dan kapan berhenti.
     */
    public function simpanSettingKontroler(Request $request, Farm $farm, WeatherService $weatherService): JsonResponse
    {
        $data = $request->validate([
            'kipas.otomatis' => 'nullable|boolean',
            'kipas.suhu_nyala' => 'required|numeric|between:20,45',
            'kipas.thi_nyala' => 'required|numeric|between:65,95',

            'misting.otomatis' => 'nullable|boolean',
            'misting.thi_nyala' => 'required|numeric|between:70,95',
            'misting.rh_maksimal' => 'required|numeric|between:50,95',

            'lampu.otomatis' => 'nullable|boolean',
            'lampu.jam_pagi_mulai' => 'required|string',
            'lampu.jam_pagi_selesai' => 'required|string',
            'lampu.jam_sore_mulai' => 'required|string',
            'lampu.jam_sore_selesai' => 'required|string',

            'pakan.otomatis' => 'nullable|boolean',
            'pakan.mode' => 'nullable|string|in:shuttle,bolak_balik',
            'pakan.durasi_detik' => 'nullable|numeric|between:1,60',
            'pakan.jam_pagi' => 'nullable|string',
            'pakan.jam_sore' => 'nullable|string',
        ]);

        $settings = [
            'kipas' => [
                'otomatis' => (bool) $request->input('kipas.otomatis', false),
                'suhu_nyala' => (float) $data['kipas']['suhu_nyala'],
                'thi_nyala' => (float) $data['kipas']['thi_nyala'],
            ],
            'misting' => [
                'otomatis' => (bool) $request->input('misting.otomatis', false),
                'thi_nyala' => (float) $data['misting']['thi_nyala'],
                'rh_maksimal' => (float) $data['misting']['rh_maksimal'],
            ],
            'lampu' => [
                'otomatis' => (bool) $request->input('lampu.otomatis', false),
                'jam_pagi_mulai' => $data['lampu']['jam_pagi_mulai'],
                'jam_pagi_selesai' => $data['lampu']['jam_pagi_selesai'],
                'jam_sore_mulai' => $data['lampu']['jam_sore_mulai'],
                'jam_sore_selesai' => $data['lampu']['jam_sore_selesai'],
            ],
            'pakan' => [
                'otomatis' => (bool) $request->input('pakan.otomatis', false),
                'mode' => $data['pakan']['mode'] ?? 'shuttle',
                'durasi_detik' => (int) ($data['pakan']['durasi_detik'] ?? 4),
                'jam_pagi' => $data['pakan']['jam_pagi'] ?? '07:00',
                'jam_sore' => $data['pakan']['jam_sore'] ?? '15:30',
            ],
        ];

        $farm->update([
            'controller_settings' => $settings,
            'controller_siap' => true,
        ]);

        $w = $weatherService->getWeather($farm);
        $kontrol = $this->evaluasiLogikaKontroler($farm, (float) $w['suhu'], (float) $w['kelembaban'], (float) $w['thi']);
        $kontrol['siap'] = (bool) $farm->controller_siap;

        return response()->json([
            'ok' => true,
            'pesan' => 'Pengaturan otomatisasi alat berhasil disimpan.',
            'kontrol' => $kontrol,
        ]);
    }

    /**
     * Evaluasi cerdas status aktuator per controller (Kipas, Misting, Lampu) berdasarkan setting otomatisasi & sensor.
     *
     * @return array{
     *     ok: bool,
     *     siap: bool,
     *     relay_kipas: bool,
     *     relay_misting: bool,
     *     relay_lampu: bool,
     *     relay_pakan: bool,
     *     settings: array,
     *     keterangan_aksi: string
     * }
     */
    public function evaluasiLogikaKontroler(Farm $farm, float $suhu, float $kelembaban, float $thi): array
    {
        $settings = $farm->getSettingsKontroler();
        $updates = [];

        // 1. Kipas Sirkulasi:
        if (!empty($settings['kipas']['otomatis'])) {
            $suhuNyala = (float) ($settings['kipas']['suhu_nyala'] ?? 31.0);
            $thiNyala  = (float) ($settings['kipas']['thi_nyala'] ?? 78.0);
            $updates['relay_kipas'] = ($suhu >= $suhuNyala || $thi >= $thiNyala);
        }

        // 2. Misting Pompa Kabut:
        if (!empty($settings['misting']['otomatis'])) {
            $thiNyala = (float) ($settings['misting']['thi_nyala'] ?? 80.0);
            $rhMax    = (float) ($settings['misting']['rh_maksimal'] ?? 75.0);
            // Safety Lock: Jika kelembaban >= rhMax, misting otomatis OFF agar sekam tidak basah
            $updates['relay_misting'] = ($thi >= $thiNyala && $kelembaban < $rhMax);
        }

        // 3. Lampu Fotoperiode:
        if (!empty($settings['lampu']['otomatis'])) {
            $jamMenit = Carbon::now('Asia/Jakarta')->format('H:i');
            $pagiMulai   = $settings['lampu']['jam_pagi_mulai'] ?? '04:00';
            $pagiSelesai = $settings['lampu']['jam_pagi_selesai'] ?? '07:00';
            $soreMulai   = $settings['lampu']['jam_sore_mulai'] ?? '18:00';
            $soreSelesai = $settings['lampu']['jam_sore_selesai'] ?? '21:00';

            $isPagi = ($jamMenit >= $pagiMulai && $jamMenit < $pagiSelesai);
            $isSore = ($jamMenit >= $soreMulai && $jamMenit < $soreSelesai);
            $updates['relay_lampu'] = ($isPagi || $isSore);
        }

        // 4. Kereta Pakan Berjalan:
        if (!empty($settings['pakan']['otomatis'])) {
            $jamMenit = Carbon::now('Asia/Jakarta')->format('H:i');
            $jamPagi  = $settings['pakan']['jam_pagi'] ?? '07:00';
            $jamSore  = $settings['pakan']['jam_sore'] ?? '15:30';
            $updates['relay_pakan'] = ($jamMenit === $jamPagi || $jamMenit === $jamSore);
        }

        if (!empty($updates)) {
            $farm->update($updates);
        }

        $aksi = [];
        if ($farm->relay_kipas) {
            $aksi[] = 'Kipas ON';
        }
        if ($farm->relay_misting) {
            $aksi[] = 'Sprinkler Atap ON';
        }
        if ($farm->relay_lampu) {
            $aksi[] = 'Lampu ON';
        }
        if ($farm->relay_pakan) {
            $aksi[] = 'Kereta Pakan ON';
        }
        if (empty($aksi)) {
            $aksi[] = 'Semua alat siaga (OFF)';
        }

        $farm->refresh();
        $adaAksi = (bool) ($farm->relay_kipas || $farm->relay_misting || $farm->relay_lampu || $farm->relay_pakan);

        return [
            'ok' => true,
            'siap' => $adaAksi,
            'relay_kipas' => (bool) $farm->relay_kipas,
            'relay_misting' => (bool) $farm->relay_misting,
            'relay_lampu' => (bool) $farm->relay_lampu,
            'relay_pakan' => (bool) $farm->relay_pakan,
            'settings' => $settings,
            'keterangan_aksi' => implode(' • ', $aksi),
        ];
    }

    /**
     * Endpoint untuk memutuskan/melepas koneksi sensor IoT (simulasi sensor dilepas/dicabut).
     */
    public function putusSensorIot(Farm $farm, WeatherService $weatherService): JsonResponse
    {
        $farm->update([
            'sensor_updated_at' => now()->subHours(2),
        ]);

        Cache::forget("weather_farm_{$farm->id}_{$farm->lokasi_kota}");
        $w = $weatherService->fetchOpenMeteo($farm);

        return response()->json([
            'ok' => true,
            'pesan' => 'Sensor IoT ESP32 berhasil diputuskan (simulasi dilepas). Sistem kembali ke estimasi BMKG.',
            'sumber' => $w['sumber'],
            'suhu' => $w['suhu'],
            'kelembaban' => $w['kelembaban'],
            'thi' => $w['thi'],
        ]);
    }

    /**
     * Endpoint acuan cuaca luar BMKG/Open-Meteo untuk sinkronisasi mikrokontroler ESP32 secara dinamis.
     * Mengembalikan data suhu luar terkini tanpa dipengaruhi oleh status sensor IoT kandang.
     */
    public function acuanBmkgLive(Farm $farm, WeatherService $weatherService): JsonResponse
    {
        $w = $weatherService->fetchOpenMeteo($farm);

        return response()->json([
            'ok' => true,
            'kota' => $w['kota'] ?? ($farm->lokasi_kota ?: 'Sidoarjo'),
            'suhu' => (float) $w['suhu'],
            'kelembaban' => (float) $w['kelembaban'],
            'cuaca_teks' => $w['cuaca_teks'] ?? 'Cerah Berawan',
            'jam_server' => now()->format('H:i'),
        ]);
    }

    public function tentang()
    {
        return view('tentang');
    }

    /**
     * Halaman Menu Khusus: Pusat Kendali Aktuator Kandang (Smart Closed-Loop Controller).
     */
    public function kontroler(WeatherService $weatherService): View
    {
        $farm = $this->farm();
        $weather = $weatherService->getWeather($farm);
        $isSensorOnline = $weatherService->isSensorOnline($farm, 60);
        $kontrol = $this->evaluasiLogikaKontroler(
            $farm,
            (float) $weather['suhu'],
            (float) $weather['kelembaban'],
            (float) $weather['thi']
        );

        return view('kontroler.index', compact('farm', 'weather', 'isSensorOnline', 'kontrol'));
    }
}
