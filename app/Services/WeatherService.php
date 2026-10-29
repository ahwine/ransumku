<?php

namespace App\Services;

use App\Models\Farm;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Layanan Cuaca Mikro & Analisis Indeks Stres Panas (THI) untuk Ayam Petelur.
 *
 * Menghubungkan geolokasi kandang dengan Open-Meteo Weather API (tanpa biaya / gratis)
 * serta mendukung input sensor IoT kandang (ESP32/DHT22) secara hibrida.
 */
class WeatherService
{
    /**
     * Daftar sentra peternakan ayam petelur utama di Indonesia beserta koordinatnya.
     *
     * @var array<string, array{nama: string, lat: float, lon: float, provinsi: string}>
     */
    public const PRESET_KOTA = [
        'Blitar' => ['nama' => 'Kab. Blitar', 'lat' => -8.0983, 'lon' => 112.1681, 'provinsi' => 'Jawa Timur'],
        'Surabaya' => ['nama' => 'Surabaya', 'lat' => -7.2575, 'lon' => 112.7521, 'provinsi' => 'Jawa Timur'],
        'Malang' => ['nama' => 'Malang', 'lat' => -7.9797, 'lon' => 112.6304, 'provinsi' => 'Jawa Timur'],
        'Kediri' => ['nama' => 'Kediri', 'lat' => -7.8480, 'lon' => 112.0178, 'provinsi' => 'Jawa Timur'],
        'Tulungagung' => ['nama' => 'Tulungagung', 'lat' => -8.0667, 'lon' => 111.9000, 'provinsi' => 'Jawa Timur'],
        'Boyolali' => ['nama' => 'Boyolali', 'lat' => -7.5333, 'lon' => 110.6000, 'provinsi' => 'Jawa Tengah'],
        'Solo' => ['nama' => 'Surakarta (Solo)', 'lat' => -7.5755, 'lon' => 110.8243, 'provinsi' => 'Jawa Tengah'],
        'Kendal' => ['nama' => 'Kendal', 'lat' => -6.9200, 'lon' => 110.2030, 'provinsi' => 'Jawa Tengah'],
        'Yogyakarta' => ['nama' => 'Yogyakarta', 'lat' => -7.7956, 'lon' => 110.3695, 'provinsi' => 'DI Yogyakarta'],
        'Ciamis' => ['nama' => 'Ciamis', 'lat' => -7.3274, 'lon' => 108.3556, 'provinsi' => 'Jawa Barat'],
        'Bogor' => ['nama' => 'Bogor', 'lat' => -6.5950, 'lon' => 106.8166, 'provinsi' => 'Jawa Barat'],
        'Medan' => ['nama' => 'Medan & Deli Serdang', 'lat' => 3.5952, 'lon' => 98.6722, 'provinsi' => 'Sumatera Utara'],
        'Lampung' => ['nama' => 'Lampung Selatan', 'lat' => -5.7167, 'lon' => 105.5833, 'provinsi' => 'Lampung'],
        'Sidrap' => ['nama' => 'Sidenreng Rappang (Sidrap)', 'lat' => -3.9242, 'lon' => 119.8058, 'provinsi' => 'Sulawesi Selatan'],
    ];

    /**
     * Mengambil kondisi cuaca mikro kandang & kalkulasi indeks stres panas (THI).
     *
     * @return array{
     *     sumber: string,
     *     kota: string,
     *     suhu: float,
     *     kelembaban: float,
     *     apparent_temp: float,
     *     angin_kmh: float,
     *     cuaca_teks: string,
     *     icon: string,
     *     icon_color: string,
     *     thi: float,
     *     thi_kategori: string,
     *     thi_kategori_singkat: string,
     *     thi_level: string,
     *     badge_class: string,
     *     card_class: string,
     *     deskripsi_risiko: string,
     *     saran_manajemen: array<string>,
     *     updated_at: string
     * }
     */
    public function getWeather(Farm $farm): array
    {
        // 1. Prioritaskan data sensor fisik IoT jika diperbarui dalam 30 menit terakhir
        if ($this->hasActiveSensor($farm)) {
            $suhu = (float) $farm->sensor_suhu;
            $kelembaban = (float) $farm->sensor_kelembaban;

            return $this->formatResult(
                sumber: 'sensor',
                kota: $farm->lokasi_kota ?: 'Kandang Lokal',
                suhu: $suhu,
                kelembaban: $kelembaban,
                apparentTemp: $suhu + 2.0,
                anginKmh: 5.0,
                weatherCode: 0,
                updatedAt: Carbon::parse($farm->sensor_updated_at)->translatedFormat('H:i \W\I\B')
            );
        }

        // 2. Jika tidak ada sensor IoT fisik, ambil data dari Open-Meteo Weather API dengan cache 15 menit
        $cacheKey = "weather_farm_{$farm->id}_{$farm->lokasi_kota}";

        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($farm) {
            return $this->fetchOpenMeteo($farm);
        });
    }

    /**
     * Memeriksa apakah sensor IoT kandang mengirim data dalam 30 menit terakhir.
     */
    public function hasActiveSensor(Farm $farm): bool
    {
        return $farm->sensor_suhu !== null
            && $farm->sensor_kelembaban !== null
            && $farm->sensor_updated_at !== null
            && Carbon::parse($farm->sensor_updated_at)->diffInMinutes(now()) <= 30;
    }

    /**
     * Memanggil Open-Meteo REST API berdasarkan koordinat peternakan.
     */
    public function fetchOpenMeteo(Farm $farm): array
    {
        $lat = $farm->latitude ?: -7.2575;
        $lon = $farm->longitude ?: 112.7521;
        $kota = $farm->lokasi_kota ?: 'Surabaya';

        try {
            $url = sprintf(
                'https://api.open-meteo.com/v1/forecast?latitude=%.4f&longitude=%.4f&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m&timezone=auto',
                $lat,
                $lon
            );

            $response = Http::timeout(4)->get($url);

            if ($response->successful()) {
                $current = $response->json('current') ?? [];
                $suhu = (float) ($current['temperature_2m'] ?? 30.5);
                $kelembaban = (float) ($current['relative_humidity_2m'] ?? 70.0);
                $apparent = (float) ($current['apparent_temperature'] ?? ($suhu + 2.5));
                $angin = (float) ($current['wind_speed_10m'] ?? 8.0);
                $wCode = (int) ($current['weather_code'] ?? 1);

                return $this->formatResult(
                    sumber: 'open_meteo',
                    kota: $kota,
                    suhu: round($suhu, 1),
                    kelembaban: round($kelembaban, 0),
                    apparentTemp: round($apparent, 1),
                    anginKmh: round($angin, 1),
                    weatherCode: $wCode,
                    updatedAt: now()->translatedFormat('H:i \W\I\B')
                );
            }

            Log::warning("Open-Meteo API non-200: HTTP {$response->status()}");
        } catch (\Throwable $e) {
            Log::warning("Open-Meteo API exception: {$e->getMessage()}");
        }

        // Fallback cerdas berbasis iklim rata-rata tropis Indonesia jika offline
        return $this->fallbackResult($kota);
    }

    /**
     * Menghitung Temperature-Humidity Index (THI) untuk unggas / ayam petelur
     * Menggunakan formula standar biometeorologi NRC / Zulovich & DeShazer (1990):
     * THI = 0.8 * T + (RH / 100) * (T - 14.4) + 46.4
     */
    public function calculateThi(float $suhu, float $kelembaban): float
    {
        $thi = (0.8 * $suhu) + (($kelembaban / 100.0) * ($suhu - 14.4)) + 46.4;

        return round($thi, 1);
    }

    /**
     * Memformat hasil analisa iklim mikrokandang & rekomendasi peternakan.
     */
    private function formatResult(
        string $sumber,
        string $kota,
        float $suhu,
        float $kelembaban,
        float $apparentTemp,
        float $anginKmh,
        int $weatherCode,
        string $updatedAt
    ): array {
        $thi = $this->calculateThi($suhu, $kelembaban);
        $wmo = $this->interpretWeatherCode($weatherCode);
        $thiPercent = round(min(100.0, max(0.0, (($thi - 60.0) / (90.0 - 60.0)) * 100.0)), 1);

        // Kategori THI untuk Ayam Petelur
        if ($thi < 72.0) {
            $thiKategori = 'Zona Nyaman (Optimal)';
            $thiSingkat = 'Nyaman';
            $thiLevel = 'safe';
            $dampakAyam = 'Ayam aktif normal, nafsu makan optimal, dan kalsifikasi kerabang telur prima.';
            $badgeClass = 'bg-brand/10 text-brand border border-brand/20';
            $cardClass = 'bg-gradient-to-br from-emerald-50/80 via-teal-50/40 to-white border-emerald-200 text-brand-dark';
            $deskripsi = 'Suhu dan kelembaban berada di zona ideal fisiologi unggas. Nafsu makan ayam optimal, penyerapan kalsium baik, dan laju bertelur berada di puncak performa.';
            $saran = [
                'Pertahankan sirkulasi udara kandang tetap lancar dan bersih.',
                'Pemberian ransum pakan dapat dilakukan sesuai jadwal rutin standar.',
                'Kualitas kerabang telur terjaga optimal karena tidak terjadi panting.',
            ];
        } elseif ($thi < 78.0) {
            $thiKategori = 'Waspada Ringan (Mild Heat Stress)';
            $thiSingkat = 'Waspada Panas';
            $thiLevel = 'warning';
            $dampakAyam = 'Ayam mulai terengah-engah ringan (panting). Nafsu makan pakan berisiko turun 5%–10%.';
            $badgeClass = 'bg-amber-soft text-amber-900 border border-amber-300';
            $cardClass = 'bg-gradient-to-br from-amber-50/90 via-orange-50/40 to-white border-amber-300 text-amber-950';
            $deskripsi = 'Ayam mulai merasakan suhu hangat dan terengah-engah ringan (panting). Nafsu makan pakan berisiko turun sekitar 5% – 10%, sedangkan konsumsi air minum meningkat.';
            $saran = [
                'Buka tirai samping kandang secara penuh untuk memaksimalkan aliran ventilasi alami.',
                'Pastikan tempat minum tidak terkena sorotan matahari langsung dan debit air cukup.',
                'Pantau apakah ada titik kandang yang terlalu pengap atau ayam berkumpul mencari angin.',
            ];
        } elseif ($thi < 84.0) {
            $thiKategori = 'Kritis Heat Stress (Severe Heat Stress)';
            $thiSingkat = 'Kritis Panas';
            $thiLevel = 'danger';
            $dampakAyam = 'Panting berat, sayap terbuka. Nafsu makan anjlok 15%–25%, risiko cangkang tipis/retak tinggi.';
            $badgeClass = 'bg-danger-soft text-danger-ink border border-danger/30';
            $cardClass = 'bg-gradient-to-br from-rose-50/90 via-orange-50/50 to-white border-rose-300 text-rose-950';
            $deskripsi = 'Tingkat stres panas tinggi! Ayam megap-megap (panting berat) dan membuka sayap. Asupan pakan anjlok drastis (15% – 25%), metabolisme kalsium terganggu, dan risiko telur berkulit tipis/retak meningkat tajam.';
            $saran = [
                'Penerapan Split Feeding: Geser jadwal pakan, berikan 60% pakan saat fajar (06.00-08.00) dan sore/malam hari saat suhu telah sejuk.',
                'Hindari mengaduk atau memberi pakan saat siang terik (11.00-14.00) agar panas pencernaan tidak menumpuk.',
                'Tambahkan Vitamin C (1 gr/liter) dan larutan elektrolit pada air minum untuk meredakan alkalosis pernapasan.',
                'Nyalakan kipas angin kandang atau semprotkan kabut air (fogging) ke atap jika memungkinkan.',
            ];
        } else {
            $thiKategori = 'Bahaya Ekstrem (Emergency / Fatal Heat Stroke)';
            $thiSingkat = 'Bahaya Ekstrem';
            $thiLevel = 'danger';
            $dampakAyam = 'BAHAYA DARURAT! Ancaman serangan jantung panas (heat stroke) & kematian massal sangat tinggi.';
            $badgeClass = 'bg-danger text-white font-black shadow-sm';
            $cardClass = 'bg-gradient-to-br from-red-100 via-rose-50 to-white border-2 border-danger text-danger-ink';
            $deskripsi = 'KONDISI DARURAT KANDANG! Indeks panas melebihi batas toleransi biologis unggas. Ancaman serangan jantung panas (heat stroke) dan mortalitas massal sangat tinggi.';
            $saran = [
                'TINDAKAN DARURAT: Guyur atap kandang dengan air atau hidupkan nozzle spray pembasahan atap segera.',
                'Kosongkan wadah pakan saat siang hari untuk mengurangi akumulasi panas tubuh akibat metabolisme pakan.',
                'Nyalakan semua blower / kipas angin darurat dengan kecepatan penuh.',
                'Sediakan air minum dingin/es yang dicampur elektrolit anti-stres secara ad libitum.',
            ];
        }

        return [
            'sumber' => $sumber,
            'kota' => $kota,
            'suhu' => $suhu,
            'kelembaban' => $kelembaban,
            'apparent_temp' => $apparentTemp,
            'angin_kmh' => $anginKmh,
            'weather_type' => $wmo['type'],
            'cuaca_teks' => $wmo['teks'],
            'icon' => $wmo['icon'],
            'icon_color' => $wmo['color'],
            'thi' => $thi,
            'thi_percent' => $thiPercent,
            'thi_kategori' => $thiKategori,
            'thi_kategori_singkat' => $thiSingkat,
            'thi_level' => $thiLevel,
            'dampak_ayam' => $dampakAyam,
            'badge_class' => $badgeClass,
            'card_class' => $cardClass,
            'deskripsi_risiko' => $deskripsi,
            'saran_manajemen' => $saran,
            'updated_at' => $updatedAt,
        ];
    }

    /**
     * Konversi kode cuaca WMO ke teks & ikon Material Symbols.
     */
    private function interpretWeatherCode(int $code): array
    {
        return match (true) {
            $code === 0 => [
                'type' => 'sunny',
                'teks' => 'Cerah',
                'icon' => 'wb_sunny',
                'color' => 'text-amber-500',
            ],
            in_array($code, [1, 2]) => [
                'type' => 'partly_cloudy',
                'teks' => 'Cerah Berawan',
                'icon' => 'partly_cloudy_day',
                'color' => 'text-amber-600',
            ],
            $code === 3 => [
                'type' => 'cloudy',
                'teks' => 'Berawan Tebal',
                'icon' => 'cloud',
                'color' => 'text-gray-500',
            ],
            in_array($code, [45, 48]) => [
                'type' => 'foggy',
                'teks' => 'Berkabut',
                'icon' => 'foggy',
                'color' => 'text-gray-400',
            ],
            in_array($code, [51, 53, 55, 61, 63, 65, 80, 81]) => [
                'type' => 'rainy',
                'teks' => 'Hujan',
                'icon' => 'rainy',
                'color' => 'text-blue-500',
            ],
            in_array($code, [82, 95, 96, 99]) => [
                'type' => 'thunderstorm',
                'teks' => 'Hujan Badai / Petir',
                'icon' => 'thunderstorm',
                'color' => 'text-indigo-600',
            ],
            default => [
                'type' => 'partly_cloudy',
                'teks' => 'Berawan',
                'icon' => 'partly_cloudy_day',
                'color' => 'text-muted',
            ],
        };
    }

    /**
     * Data cadangan realistis saat jaringan eksternal tidak dapat dihubungi.
     */
    private function fallbackResult(string $kota): array
    {
        return $this->formatResult(
            sumber: 'estimasi_lokal',
            kota: $kota,
            suhu: 31.0,
            kelembaban: 72.0,
            apparentTemp: 35.0,
            anginKmh: 8.0,
            weatherCode: 1,
            updatedAt: now()->translatedFormat('H:i \W\I\B')
        );
    }
}

