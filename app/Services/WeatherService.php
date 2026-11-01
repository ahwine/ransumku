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
     * Daftar sentra peternakan ayam petelur dan kota/kabupaten strategis di seluruh 38 provinsi Indonesia.
     * Dilengkapi koordinat geolokasi presisi untuk sinkronisasi cuaca mikro Open-Meteo.
     *
     * @var array<string, array{nama: string, lat: float, lon: float, provinsi: string, wilayah: string}>
     */
    public const PRESET_KOTA = [
        // ==========================================
        // JAWA TIMUR (Sentra Telur Terbesar Nasional)
        // ==========================================
        'Blitar' => ['nama' => 'Kab. Blitar', 'lat' => -8.0983, 'lon' => 112.1681, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Surabaya' => ['nama' => 'Surabaya', 'lat' => -7.2575, 'lon' => 112.7521, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Malang' => ['nama' => 'Malang', 'lat' => -7.9797, 'lon' => 112.6304, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Kediri' => ['nama' => 'Kediri', 'lat' => -7.8480, 'lon' => 112.0178, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Tulungagung' => ['nama' => 'Tulungagung', 'lat' => -8.0667, 'lon' => 111.9000, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Jombang' => ['nama' => 'Jombang', 'lat' => -7.5461, 'lon' => 112.2331, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Pasuruan' => ['nama' => 'Pasuruan', 'lat' => -7.6453, 'lon' => 112.9075, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Banyuwangi' => ['nama' => 'Banyuwangi', 'lat' => -8.2192, 'lon' => 114.3691, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Jember' => ['nama' => 'Jember', 'lat' => -8.1724, 'lon' => 113.7007, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Lamongan' => ['nama' => 'Lamongan', 'lat' => -7.1282, 'lon' => 112.3135, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Magetan' => ['nama' => 'Magetan', 'lat' => -7.6539, 'lon' => 111.3283, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Mojokerto' => ['nama' => 'Mojokerto', 'lat' => -7.4726, 'lon' => 112.4381, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Sidoarjo' => ['nama' => 'Sidoarjo', 'lat' => -7.4478, 'lon' => 112.7183, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Tuban' => ['nama' => 'Tuban', 'lat' => -6.8976, 'lon' => 112.0649, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Bojonegoro' => ['nama' => 'Bojonegoro', 'lat' => -7.1502, 'lon' => 111.8817, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Probolinggo' => ['nama' => 'Probolinggo', 'lat' => -7.7543, 'lon' => 113.2159, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],
        'Sumenep' => ['nama' => 'Sumenep (Madura)', 'lat' => -7.0051, 'lon' => 113.8604, 'provinsi' => 'Jawa Timur', 'wilayah' => 'Jawa'],

        // ==========================================
        // JAWA TENGAH & DI YOGYAKARTA
        // ==========================================
        'Boyolali' => ['nama' => 'Boyolali', 'lat' => -7.5333, 'lon' => 110.6000, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Solo' => ['nama' => 'Surakarta (Solo)', 'lat' => -7.5755, 'lon' => 110.8243, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Kendal' => ['nama' => 'Kendal', 'lat' => -6.9200, 'lon' => 110.2030, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Semarang' => ['nama' => 'Semarang', 'lat' => -6.9666, 'lon' => 110.4166, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Magelang' => ['nama' => 'Magelang', 'lat' => -7.4705, 'lon' => 110.2178, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Klaten' => ['nama' => 'Klaten', 'lat' => -7.7056, 'lon' => 110.6014, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Sukoharjo' => ['nama' => 'Sukoharjo', 'lat' => -7.6833, 'lon' => 110.8333, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Karanganyar' => ['nama' => 'Karanganyar', 'lat' => -7.5961, 'lon' => 110.9514, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Sragen' => ['nama' => 'Sragen', 'lat' => -7.4278, 'lon' => 111.0222, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Wonogiri' => ['nama' => 'Wonogiri', 'lat' => -7.8167, 'lon' => 110.9333, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Banyumas' => ['nama' => 'Banyumas (Purwokerto)', 'lat' => -7.4244, 'lon' => 109.2302, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Cilacap' => ['nama' => 'Cilacap', 'lat' => -7.7167, 'lon' => 109.0167, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Brebes' => ['nama' => 'Brebes', 'lat' => -6.8703, 'lon' => 109.0415, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Tegal' => ['nama' => 'Tegal', 'lat' => -6.8694, 'lon' => 109.1402, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Kudus' => ['nama' => 'Kudus', 'lat' => -6.8048, 'lon' => 110.8405, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Pati' => ['nama' => 'Pati', 'lat' => -6.7558, 'lon' => 111.0378, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Jepara' => ['nama' => 'Jepara', 'lat' => -6.5928, 'lon' => 110.6778, 'provinsi' => 'Jawa Tengah', 'wilayah' => 'Jawa'],
        'Yogyakarta' => ['nama' => 'Kota Yogyakarta', 'lat' => -7.7956, 'lon' => 110.3695, 'provinsi' => 'DI Yogyakarta', 'wilayah' => 'Jawa'],
        'Sleman' => ['nama' => 'Sleman', 'lat' => -7.7156, 'lon' => 110.3556, 'provinsi' => 'DI Yogyakarta', 'wilayah' => 'Jawa'],
        'Bantul' => ['nama' => 'Bantul', 'lat' => -7.8897, 'lon' => 110.3289, 'provinsi' => 'DI Yogyakarta', 'wilayah' => 'Jawa'],
        'KulonProgo' => ['nama' => 'Kulon Progo', 'lat' => -7.8286, 'lon' => 110.1583, 'provinsi' => 'DI Yogyakarta', 'wilayah' => 'Jawa'],
        'Gunungkidul' => ['nama' => 'Gunungkidul', 'lat' => -7.9606, 'lon' => 110.6033, 'provinsi' => 'DI Yogyakarta', 'wilayah' => 'Jawa'],

        // ==========================================
        // JAWA BARAT, BANTEN, & DKI JAKARTA
        // ==========================================
        'Ciamis' => ['nama' => 'Ciamis', 'lat' => -7.3274, 'lon' => 108.3556, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Bogor' => ['nama' => 'Bogor', 'lat' => -6.5950, 'lon' => 106.8166, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Bandung' => ['nama' => 'Bandung', 'lat' => -6.9175, 'lon' => 107.6191, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Sukabumi' => ['nama' => 'Sukabumi', 'lat' => -6.9277, 'lon' => 106.9300, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Cianjur' => ['nama' => 'Cianjur', 'lat' => -6.8228, 'lon' => 107.1394, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Garut' => ['nama' => 'Garut', 'lat' => -7.2278, 'lon' => 107.9086, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Tasikmalaya' => ['nama' => 'Tasikmalaya', 'lat' => -7.3274, 'lon' => 108.2207, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Kuningan' => ['nama' => 'Kuningan', 'lat' => -6.9767, 'lon' => 108.4833, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Cirebon' => ['nama' => 'Cirebon', 'lat' => -6.7063, 'lon' => 108.5570, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Subang' => ['nama' => 'Subang', 'lat' => -6.5716, 'lon' => 107.7587, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Karawang' => ['nama' => 'Karawang', 'lat' => -6.3056, 'lon' => 107.3078, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Bekasi' => ['nama' => 'Bekasi', 'lat' => -6.2383, 'lon' => 106.9756, 'provinsi' => 'Jawa Barat', 'wilayah' => 'Jawa'],
        'Jakarta' => ['nama' => 'DKI Jakarta', 'lat' => -6.2088, 'lon' => 106.8456, 'provinsi' => 'DKI Jakarta', 'wilayah' => 'Jawa'],
        'Serang' => ['nama' => 'Serang', 'lat' => -6.1200, 'lon' => 106.1503, 'provinsi' => 'Banten', 'wilayah' => 'Jawa'],
        'Tangerang' => ['nama' => 'Tangerang', 'lat' => -6.1783, 'lon' => 106.6319, 'provinsi' => 'Banten', 'wilayah' => 'Jawa'],
        'Lebak' => ['nama' => 'Lebak (Rangkasbitung)', 'lat' => -6.5417, 'lon' => 106.2514, 'provinsi' => 'Banten', 'wilayah' => 'Jawa'],
        'Pandeglang' => ['nama' => 'Pandeglang', 'lat' => -6.3089, 'lon' => 106.1067, 'provinsi' => 'Banten', 'wilayah' => 'Jawa'],

        // ==========================================
        // SUMATERA
        // ==========================================
        'BandaAceh' => ['nama' => 'Banda Aceh', 'lat' => 5.5483, 'lon' => 95.3238, 'provinsi' => 'Aceh', 'wilayah' => 'Sumatera'],
        'Bireuen' => ['nama' => 'Bireuen', 'lat' => 5.2000, 'lon' => 96.7000, 'provinsi' => 'Aceh', 'wilayah' => 'Sumatera'],
        'Medan' => ['nama' => 'Medan & Deli Serdang', 'lat' => 3.5952, 'lon' => 98.6722, 'provinsi' => 'Sumatera Utara', 'wilayah' => 'Sumatera'],
        'Asahan' => ['nama' => 'Asahan (Kisaran)', 'lat' => 2.9833, 'lon' => 99.6167, 'provinsi' => 'Sumatera Utara', 'wilayah' => 'Sumatera'],
        'Siantar' => ['nama' => 'Pematangsiantar', 'lat' => 2.9600, 'lon' => 99.0600, 'provinsi' => 'Sumatera Utara', 'wilayah' => 'Sumatera'],
        'Padang' => ['nama' => 'Padang', 'lat' => -0.9471, 'lon' => 100.4172, 'provinsi' => 'Sumatera Barat', 'wilayah' => 'Sumatera'],
        'Payakumbuh' => ['nama' => 'Payakumbuh & 50 Kota', 'lat' => -0.2244, 'lon' => 100.6300, 'provinsi' => 'Sumatera Barat', 'wilayah' => 'Sumatera'],
        'TanahDatar' => ['nama' => 'Tanah Datar (Batusangkar)', 'lat' => -0.4578, 'lon' => 100.5964, 'provinsi' => 'Sumatera Barat', 'wilayah' => 'Sumatera'],
        'Pekanbaru' => ['nama' => 'Pekanbaru', 'lat' => 0.5071, 'lon' => 101.4478, 'provinsi' => 'Riau', 'wilayah' => 'Sumatera'],
        'Kampar' => ['nama' => 'Kampar (Bangkinang)', 'lat' => 0.3375, 'lon' => 101.0256, 'provinsi' => 'Riau', 'wilayah' => 'Sumatera'],
        'Batam' => ['nama' => 'Batam', 'lat' => 1.1301, 'lon' => 104.0529, 'provinsi' => 'Kepulauan Riau', 'wilayah' => 'Sumatera'],
        'Tanjungpinang' => ['nama' => 'Tanjungpinang', 'lat' => 0.9167, 'lon' => 104.4500, 'provinsi' => 'Kepulauan Riau', 'wilayah' => 'Sumatera'],
        'Jambi' => ['nama' => 'Kota Jambi', 'lat' => -1.6101, 'lon' => 103.6131, 'provinsi' => 'Jambi', 'wilayah' => 'Sumatera'],
        'MuaroJambi' => ['nama' => 'Muaro Jambi', 'lat' => -1.5333, 'lon' => 103.7833, 'provinsi' => 'Jambi', 'wilayah' => 'Sumatera'],
        'Palembang' => ['nama' => 'Palembang', 'lat' => -2.9761, 'lon' => 104.7754, 'provinsi' => 'Sumatera Selatan', 'wilayah' => 'Sumatera'],
        'Banyuasin' => ['nama' => 'Banyuasin', 'lat' => -2.8833, 'lon' => 104.3833, 'provinsi' => 'Sumatera Selatan', 'wilayah' => 'Sumatera'],
        'OganIlir' => ['nama' => 'Ogan Ilir (Indralaya)', 'lat' => -3.2289, 'lon' => 104.6528, 'provinsi' => 'Sumatera Selatan', 'wilayah' => 'Sumatera'],
        'Bengkulu' => ['nama' => 'Kota Bengkulu', 'lat' => -3.8004, 'lon' => 102.2655, 'provinsi' => 'Bengkulu', 'wilayah' => 'Sumatera'],
        'Pangkalpinang' => ['nama' => 'Pangkalpinang', 'lat' => -2.1333, 'lon' => 106.1167, 'provinsi' => 'Kepulauan Bangka Belitung', 'wilayah' => 'Sumatera'],
        'Lampung' => ['nama' => 'Lampung Selatan', 'lat' => -5.7167, 'lon' => 105.5833, 'provinsi' => 'Lampung', 'wilayah' => 'Sumatera'],
        'BandarLampung' => ['nama' => 'Bandar Lampung', 'lat' => -5.4500, 'lon' => 105.2667, 'provinsi' => 'Lampung', 'wilayah' => 'Sumatera'],
        'LampungTengah' => ['nama' => 'Lampung Tengah (Gunung Sugih)', 'lat' => -4.9667, 'lon' => 105.2167, 'provinsi' => 'Lampung', 'wilayah' => 'Sumatera'],
        'LampungTimur' => ['nama' => 'Lampung Timur (Sukadana)', 'lat' => -5.1167, 'lon' => 105.6833, 'provinsi' => 'Lampung', 'wilayah' => 'Sumatera'],

        // ==========================================
        // BALI & NUSA TENGGARA
        // ==========================================
        'Denpasar' => ['nama' => 'Denpasar', 'lat' => -8.6705, 'lon' => 115.2126, 'provinsi' => 'Bali', 'wilayah' => 'Bali & Nusa Tenggara'],
        'Tabanan' => ['nama' => 'Tabanan', 'lat' => -8.5411, 'lon' => 115.1253, 'provinsi' => 'Bali', 'wilayah' => 'Bali & Nusa Tenggara'],
        'Gianyar' => ['nama' => 'Gianyar', 'lat' => -8.5433, 'lon' => 115.3267, 'provinsi' => 'Bali', 'wilayah' => 'Bali & Nusa Tenggara'],
        'Bangli' => ['nama' => 'Bangli', 'lat' => -8.4542, 'lon' => 115.3550, 'provinsi' => 'Bali', 'wilayah' => 'Bali & Nusa Tenggara'],
        'Mataram' => ['nama' => 'Mataram (Lombok)', 'lat' => -8.5833, 'lon' => 116.1167, 'provinsi' => 'Nusa Tenggara Barat', 'wilayah' => 'Bali & Nusa Tenggara'],
        'LombokTimur' => ['nama' => 'Lombok Timur (Selong)', 'lat' => -8.6500, 'lon' => 116.5333, 'provinsi' => 'Nusa Tenggara Barat', 'wilayah' => 'Bali & Nusa Tenggara'],
        'Sumbawa' => ['nama' => 'Sumbawa', 'lat' => -8.5000, 'lon' => 117.4167, 'provinsi' => 'Nusa Tenggara Barat', 'wilayah' => 'Bali & Nusa Tenggara'],
        'Bima' => ['nama' => 'Kota Bima', 'lat' => -8.4500, 'lon' => 118.7167, 'provinsi' => 'Nusa Tenggara Barat', 'wilayah' => 'Bali & Nusa Tenggara'],
        'Kupang' => ['nama' => 'Kupang', 'lat' => -10.1772, 'lon' => 123.6070, 'provinsi' => 'Nusa Tenggara Timur', 'wilayah' => 'Bali & Nusa Tenggara'],
        'TTS' => ['nama' => 'Timor Tengah Selatan (Soe)', 'lat' => -9.8608, 'lon' => 124.2831, 'provinsi' => 'Nusa Tenggara Timur', 'wilayah' => 'Bali & Nusa Tenggara'],
        'Sikka' => ['nama' => 'Sikka (Maumere)', 'lat' => -8.6167, 'lon' => 122.2167, 'provinsi' => 'Nusa Tenggara Timur', 'wilayah' => 'Bali & Nusa Tenggara'],

        // ==========================================
        // KALIMANTAN
        // ==========================================
        'Pontianak' => ['nama' => 'Pontianak', 'lat' => -0.0263, 'lon' => 109.3425, 'provinsi' => 'Kalimantan Barat', 'wilayah' => 'Kalimantan'],
        'Singkawang' => ['nama' => 'Singkawang', 'lat' => 0.9075, 'lon' => 108.9856, 'provinsi' => 'Kalimantan Barat', 'wilayah' => 'Kalimantan'],
        'KubuRaya' => ['nama' => 'Kubu Raya', 'lat' => -0.1500, 'lon' => 109.4167, 'provinsi' => 'Kalimantan Barat', 'wilayah' => 'Kalimantan'],
        'Palangkaraya' => ['nama' => 'Palangka Raya', 'lat' => -2.2167, 'lon' => 113.9167, 'provinsi' => 'Kalimantan Tengah', 'wilayah' => 'Kalimantan'],
        'Kotim' => ['nama' => 'Kotawaringin Timur (Sampit)', 'lat' => -2.5333, 'lon' => 112.9500, 'provinsi' => 'Kalimantan Tengah', 'wilayah' => 'Kalimantan'],
        'Banjarmasin' => ['nama' => 'Banjarmasin', 'lat' => -3.3167, 'lon' => 114.5900, 'provinsi' => 'Kalimantan Selatan', 'wilayah' => 'Kalimantan'],
        'Banjarbaru' => ['nama' => 'Banjarbaru', 'lat' => -3.4572, 'lon' => 114.8103, 'provinsi' => 'Kalimantan Selatan', 'wilayah' => 'Kalimantan'],
        'TanahLaut' => ['nama' => 'Tanah Laut (Pelaihari)', 'lat' => -3.7944, 'lon' => 114.8625, 'provinsi' => 'Kalimantan Selatan', 'wilayah' => 'Kalimantan'],
        'Samarinda' => ['nama' => 'Samarinda', 'lat' => -0.5022, 'lon' => 117.1536, 'provinsi' => 'Kalimantan Timur', 'wilayah' => 'Kalimantan'],
        'Balikpapan' => ['nama' => 'Balikpapan', 'lat' => -1.2379, 'lon' => 116.8289, 'provinsi' => 'Kalimantan Timur', 'wilayah' => 'Kalimantan'],
        'Kukar' => ['nama' => 'Kutai Kartanegara (Tenggarong)', 'lat' => -0.4167, 'lon' => 116.9833, 'provinsi' => 'Kalimantan Timur', 'wilayah' => 'Kalimantan'],
        'Tarakan' => ['nama' => 'Tarakan', 'lat' => 3.3000, 'lon' => 117.6333, 'provinsi' => 'Kalimantan Utara', 'wilayah' => 'Kalimantan'],
        'TanjungSelor' => ['nama' => 'Bulungan (Tanjung Selor)', 'lat' => 2.8333, 'lon' => 117.3667, 'provinsi' => 'Kalimantan Utara', 'wilayah' => 'Kalimantan'],

        // ==========================================
        // SULAWESI
        // ==========================================
        'Sidrap' => ['nama' => 'Sidenreng Rappang (Sidrap)', 'lat' => -3.9242, 'lon' => 119.8058, 'provinsi' => 'Sulawesi Selatan', 'wilayah' => 'Sulawesi'],
        'Makassar' => ['nama' => 'Makassar', 'lat' => -5.1477, 'lon' => 119.4327, 'provinsi' => 'Sulawesi Selatan', 'wilayah' => 'Sulawesi'],
        'Maros' => ['nama' => 'Maros', 'lat' => -5.0042, 'lon' => 119.5739, 'provinsi' => 'Sulawesi Selatan', 'wilayah' => 'Sulawesi'],
        'Gowa' => ['nama' => 'Gowa (Sungguminasa)', 'lat' => -5.2000, 'lon' => 119.4500, 'provinsi' => 'Sulawesi Selatan', 'wilayah' => 'Sulawesi'],
        'Pinrang' => ['nama' => 'Pinrang', 'lat' => -3.7917, 'lon' => 119.6500, 'provinsi' => 'Sulawesi Selatan', 'wilayah' => 'Sulawesi'],
        'Bone' => ['nama' => 'Bone (Watampone)', 'lat' => -4.5386, 'lon' => 120.3278, 'provinsi' => 'Sulawesi Selatan', 'wilayah' => 'Sulawesi'],
        'Mamuju' => ['nama' => 'Mamuju', 'lat' => -2.6748, 'lon' => 118.8884, 'provinsi' => 'Sulawesi Barat', 'wilayah' => 'Sulawesi'],
        'Polman' => ['nama' => 'Polewali Mandar', 'lat' => -3.4333, 'lon' => 119.3333, 'provinsi' => 'Sulawesi Barat', 'wilayah' => 'Sulawesi'],
        'Palu' => ['nama' => 'Palu', 'lat' => -0.9003, 'lon' => 119.8780, 'provinsi' => 'Sulawesi Tengah', 'wilayah' => 'Sulawesi'],
        'Sigi' => ['nama' => 'Sigi', 'lat' => -1.1333, 'lon' => 119.9667, 'provinsi' => 'Sulawesi Tengah', 'wilayah' => 'Sulawesi'],
        'Kendari' => ['nama' => 'Kendari', 'lat' => -3.9985, 'lon' => 122.5126, 'provinsi' => 'Sulawesi Tenggara', 'wilayah' => 'Sulawesi'],
        'Konawe' => ['nama' => 'Konawe (Unaaha)', 'lat' => -3.8500, 'lon' => 122.0500, 'provinsi' => 'Sulawesi Tenggara', 'wilayah' => 'Sulawesi'],
        'Gorontalo' => ['nama' => 'Kota Gorontalo', 'lat' => 0.5435, 'lon' => 123.0568, 'provinsi' => 'Gorontalo', 'wilayah' => 'Sulawesi'],
        'KabGorontalo' => ['nama' => 'Kab. Gorontalo (Limboto)', 'lat' => 0.6272, 'lon' => 122.9814, 'provinsi' => 'Gorontalo', 'wilayah' => 'Sulawesi'],
        'Manado' => ['nama' => 'Manado', 'lat' => 1.4748, 'lon' => 124.8428, 'provinsi' => 'Sulawesi Utara', 'wilayah' => 'Sulawesi'],
        'Minahasa' => ['nama' => 'Minahasa (Tondano)', 'lat' => 1.3069, 'lon' => 124.9125, 'provinsi' => 'Sulawesi Utara', 'wilayah' => 'Sulawesi'],
        'Tomohon' => ['nama' => 'Tomohon', 'lat' => 1.3283, 'lon' => 124.8406, 'provinsi' => 'Sulawesi Utara', 'wilayah' => 'Sulawesi'],

        // ==========================================
        // MALUKU & PAPUA
        // ==========================================
        'Ambon' => ['nama' => 'Ambon', 'lat' => -3.6547, 'lon' => 128.1906, 'provinsi' => 'Maluku', 'wilayah' => 'Maluku & Papua'],
        'MalukuTengah' => ['nama' => 'Maluku Tengah (Masohi)', 'lat' => -3.3000, 'lon' => 128.9667, 'provinsi' => 'Maluku', 'wilayah' => 'Maluku & Papua'],
        'Ternate' => ['nama' => 'Ternate', 'lat' => 0.7893, 'lon' => 127.3610, 'provinsi' => 'Maluku Utara', 'wilayah' => 'Maluku & Papua'],
        'Tidore' => ['nama' => 'Tidore Kepulauan', 'lat' => 0.6833, 'lon' => 127.4000, 'provinsi' => 'Maluku Utara', 'wilayah' => 'Maluku & Papua'],
        'Jayapura' => ['nama' => 'Jayapura', 'lat' => -2.5337, 'lon' => 140.7181, 'provinsi' => 'Papua', 'wilayah' => 'Maluku & Papua'],
        'Manokwari' => ['nama' => 'Manokwari', 'lat' => -0.8615, 'lon' => 134.0620, 'provinsi' => 'Papua Barat', 'wilayah' => 'Maluku & Papua'],
        'Sorong' => ['nama' => 'Sorong', 'lat' => -0.8762, 'lon' => 131.2558, 'provinsi' => 'Papua Barat Daya', 'wilayah' => 'Maluku & Papua'],
        'Merauke' => ['nama' => 'Merauke', 'lat' => -8.4991, 'lon' => 140.4019, 'provinsi' => 'Papua Selatan', 'wilayah' => 'Maluku & Papua'],
        'Nabire' => ['nama' => 'Nabire', 'lat' => -3.3667, 'lon' => 135.4833, 'provinsi' => 'Papua Tengah', 'wilayah' => 'Maluku & Papua'],
        'Wamena' => ['nama' => 'Jayawijaya (Wamena)', 'lat' => -4.0833, 'lon' => 138.9500, 'provinsi' => 'Papua Pegunungan', 'wilayah' => 'Maluku & Papua'],
    ];

    /**
     * Kode Wilayah Administrasi (Kemendagri / BMKG adm4) untuk sentra peternakan utama di Indonesia.
     * Menggunakan API Resmi Publik BMKG (api.bmkg.go.id) berkecepatan tinggi domestik.
     *
     * @var array<string, string>
     */
    public const BMKG_ADM4 = [
        'Sidoarjo' => '35.15.08.2001',
        'Surabaya' => '35.78.01.1001',
        'Blitar' => '35.05.01.2001',
        'Malang' => '35.73.01.1001',
        'Kediri' => '35.71.01.1001',
        'Tulungagung' => '35.04.01.2001',
        'Jombang' => '35.17.01.2001',
        'Pasuruan' => '35.75.01.1001',
        'Banyuwangi' => '35.10.01.2001',
        'Jember' => '35.09.01.2001',
        'Lamongan' => '35.24.01.2001',
        'Magetan' => '35.20.01.2001',
        'Mojokerto' => '35.76.01.1001',
        'Tuban' => '35.23.01.2001',
        'Bojonegoro' => '35.22.01.2001',
        'Probolinggo' => '35.74.01.1001',
        'Boyolali' => '33.09.01.2001',
        'Solo' => '33.72.01.1001',
        'Semarang' => '33.74.01.1001',
        'Kendal' => '33.24.01.2001',
        'Yogyakarta' => '34.71.01.1001',
        'Ciamis' => '32.07.01.2001',
        'Bogor' => '32.71.01.1001',
        'Bandung' => '32.73.01.1001',
        'Jakarta' => '31.71.01.1001',
        'Medan' => '12.71.01.1001',
        'Padang' => '13.71.01.1001',
        'Denpasar' => '51.71.01.1001',
        'Makassar' => '73.71.01.1001',
        'Sidrap' => '73.14.01.2001',
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
        // 1. Prioritaskan data sensor fisik IoT jika aktif terhubung
        if ($this->hasActiveSensor($farm)) {
            $suhu = (float) $farm->sensor_suhu;
            $kelembaban = (float) $farm->sensor_kelembaban;

            return $this->formatResult(
                sumber: 'sensor',
                kota: $farm->lokasi_kota ?: 'Kandang Lokal',
                suhu: $suhu,
                kelembaban: $kelembaban,
                apparentTemp: round($suhu + (($kelembaban / 100.0) * 4.0), 1),
                anginKmh: 5.0,
                weatherCode: 0,
                updatedAt: Carbon::parse($farm->sensor_updated_at)->translatedFormat('H:i \W\I\B')
            );
        }

        // 2. Jika sensor IoT fisik tidak terhubung secara live, gunakan data murni real-time dari Open-Meteo Weather API
        $cacheKey = "weather_farm_{$farm->id}_{$farm->lokasi_kota}";

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($farm) {
            return $this->fetchOpenMeteo($farm);
        });
    }

    /**
     * Memeriksa apakah sensor IoT kandang mengirim data dalam 60 detik terakhir.
     */
    public function hasActiveSensor(Farm $farm): bool
    {
        return $this->isSensorOnline($farm, 60);
    }

    /**
     * Memeriksa apakah sensor IoT kandang aktif online (mengirim denyut heartbeat dalam 60 detik terakhir).
     */
    public function isSensorOnline(Farm $farm, int $maxSeconds = 60): bool
    {
        return $farm->sensor_suhu !== null
            && $farm->sensor_kelembaban !== null
            && $farm->sensor_updated_at !== null
            && Carbon::parse($farm->sensor_updated_at)->diffInSeconds(now()) <= $maxSeconds;
    }

    /**
     * Mengambil data cuaca mikro kandang secara real-time.
     * Mengutamakan Open-Meteo Weather API secara real-time, lalu failover ke API Resmi BMKG RI jika terjadi timeout/gangguan jaringan.
     */
    public function fetchOpenMeteo(Farm $farm): array
    {
        $lat = $farm->latitude ?: -7.2575;
        $lon = $farm->longitude ?: 112.7521;
        $kota = $farm->lokasi_kota ?: 'Surabaya';

        // 1. Coba Open-Meteo Weather API
        try {
            $url = sprintf(
                'https://api.open-meteo.com/v1/forecast?latitude=%.4f&longitude=%.4f&current=temperature_2m,relative_humidity_2m,apparent_temperature,weather_code,wind_speed_10m&timezone=auto',
                $lat,
                $lon
            );

            $response = Http::withoutVerifying()
                ->retry(2, 50)
                ->timeout(5)
                ->get($url);

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
                    updatedAt: Carbon::now('Asia/Jakarta')->translatedFormat('H:i \W\I\B')
                );
            }

            Log::warning("Open-Meteo API non-200: HTTP {$response->status()}");
        } catch (\Throwable $e) {
            Log::warning("Open-Meteo API exception: {$e->getMessage()}");
        }

        // 2. Jika Open-Meteo gagal/timeout (misal cURL 35 di hotspot HP), failover ke API BMKG Resmi Domestik
        $bmkg = $this->fetchBmkgOfficial($farm);
        if ($bmkg !== null) {
            return $bmkg;
        }

        // 3. Fallback cerdas berbasis siklus waktu jika seluruh koneksi internet luar terputus
        return $this->fallbackResult($kota);
    }

    /**
     * Memanggil API Resmi BMKG RI (api.bmkg.go.id) untuk wilayah peternakan secara real-time.
     */
    public function fetchBmkgOfficial(Farm $farm): ?array
    {
        $kota = $farm->lokasi_kota ?: 'Sidoarjo';
        $adm4 = self::BMKG_ADM4[$kota] ?? null;

        if (! $adm4) {
            return null;
        }

        try {
            $url = "https://api.bmkg.go.id/publik/prakiraan-cuaca?adm4={$adm4}";
            $response = Http::withoutVerifying()
                ->retry(2, 100)
                ->timeout(6)
                ->get($url);

            if ($response->successful()) {
                $days = $response->json('data.0.cuaca') ?? [];
                $todaySlots = $days[0] ?? [];
                if (empty($todaySlots)) {
                    return null;
                }

                $now = now();
                $closestSlot = null;
                $smallestDiff = PHP_INT_MAX;

                foreach ($todaySlots as $slot) {
                    $slotTime = isset($slot['datetime']) ? Carbon::parse($slot['datetime']) : (isset($slot['local_datetime']) ? Carbon::parse($slot['local_datetime'], 'Asia/Jakarta') : null);
                    if ($slotTime) {
                        $diff = abs($now->diffInMinutes($slotTime, false));
                        if ($diff < $smallestDiff) {
                            $smallestDiff = $diff;
                            $closestSlot = $slot;
                        }
                    }
                }

                if (! $closestSlot && ! empty($todaySlots)) {
                    $closestSlot = $todaySlots[0];
                }

                if ($closestSlot) {
                    $suhu = (float) ($closestSlot['t'] ?? 34.0);
                    $kelembaban = (float) ($closestSlot['hu'] ?? 45.0);
                    $anginKmh = (float) ($closestSlot['ws'] ?? 10.0);
                    $apparent = round($suhu + (($kelembaban / 100.0) * 4.0), 1);
                    $wCode = (int) ($closestSlot['weather'] ?? 1);

                    return $this->formatResult(
                        sumber: 'open_meteo',
                        kota: $kota,
                        suhu: round($suhu, 1),
                        kelembaban: round($kelembaban, 0),
                        apparentTemp: $apparent,
                        anginKmh: round($anginKmh, 1),
                        weatherCode: $wCode,
                        updatedAt: Carbon::now('Asia/Jakarta')->translatedFormat('H:i \W\I\B')
                    );
                }
            }
        } catch (\Throwable $e) {
            Log::info("BMKG Official API skipped: {$e->getMessage()}");
        }

        return null;
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
     * Estimasi darurat berbasis siklus waktu nyata (hanya jika seluruh API luar tidak dapat dihubungi).
     * Secara cerdas menghitung suhu berdasarkan jam saat ini (siang 34 °C, sore 30 °C, malam 26 °C).
     */
    private function fallbackResult(string $kota): array
    {
        $jam = (int) Carbon::now('Asia/Jakarta')->format('H');
        $suhu = match (true) {
            $jam >= 11 && $jam <= 14 => 34.5,
            $jam >= 9 && $jam < 11 => 32.5,
            $jam > 14 && $jam <= 16 => 32.0,
            $jam > 16 && $jam <= 18 => 29.5,
            $jam > 18 && $jam <= 22 => 27.0,
            default => 25.0,
        };
        $kelembaban = match (true) {
            $jam >= 11 && $jam <= 14 => 42.0,
            $jam >= 9 && $jam <= 16 => 55.0,
            default => 80.0,
        };

        return $this->formatResult(
            sumber: 'estimasi_lokal',
            kota: $kota,
            suhu: $suhu,
            kelembaban: $kelembaban,
            apparentTemp: round($suhu + 3.0, 1),
            anginKmh: 8.0,
            weatherCode: ($jam >= 18 || $jam < 6) ? 0 : 1,
            updatedAt: Carbon::now('Asia/Jakarta')->translatedFormat('H:i \W\I\B')
        );
    }
}

