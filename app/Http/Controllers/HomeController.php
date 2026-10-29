<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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

        $alerts = $farm->alerts()->where('tanggal_data', '>=', now()->subDays(5))->latest('tanggal_data')->latest('id')->get();
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
                    'backgroundColor' => '#2F6B3A',
                    'borderRadius' => 6,
                ],
            ],
        ];

        $pakanGrData = $logs->isNotEmpty()
            ? $logs->map(fn ($l) => $farm->jumlah_ayam > 0 ? round(($l->pakan_kg * 1000) / $farm->jumlah_ayam, 1) : 0)->all()
            : [0, 0, 0, 0, 0, 0, 0];

        $chartPakan = [
            'type' => 'line',
            'labels' => $chartLabels,
            'legend' => true,
            'datasets' => [
                [
                    'label' => 'Konsumsi (gr/ekor)',
                    'data' => $pakanGrData,
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => '#F59E0B22',
                    'fill' => true,
                    'tension' => 0.35,
                ],
                [
                    'label' => 'Standar (gr)',
                    'data' => array_fill(0, count($chartLabels), $standarPakanGr),
                    'borderColor' => '#9CA3AF',
                    'borderDash' => [4, 4],
                    'fill' => false,
                    'tension' => 0,
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
            'datasets' => [
                [
                    'label' => 'Populasi (ekor)',
                    'data' => $populasiData,
                    'borderColor' => '#2563EB',
                    'backgroundColor' => '#2563EB15',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
        ];

        return view('beranda', compact(
            'farm', 'logs', 'hariIni', 'ration', 'biayaKgTelur', 'alerts', 'hargaUsangHari',
            'totalTelurIni', 'totalTelurKgIni', 'rataGrTelur', 'hdPersen', 'telurUtuh', 'telurBentes',
            'standarPakanGr', 'totalPakanIni', 'gramPerEkor', 'totalMatiIni',
            'eggMass', 'fcr', 'chartTelur', 'chartPakan', 'chartPopulasi',
            'weather', 'presetKota'
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

        return response()->json([
            'ok' => true,
            'pesan' => 'Data sensor IoT suhu & kelembaban kandang berhasil dicatat.',
            'suhu' => $w['suhu'],
            'kelembaban' => $w['kelembaban'],
            'thi' => $w['thi'],
            'status' => $w['thi_kategori'],
            'level' => $w['thi_level'],
            'saran_manajemen' => $w['saran_manajemen'],
        ]);
    }

    public function tentang()
    {
        return view('tentang');
    }
}
