<?php

namespace Tests\Unit;

use App\Models\Farm;
use App\Models\User;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    use RefreshDatabase;

    private WeatherService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new WeatherService();
    }

    public function test_kalkulasi_thi_akurat_sesuai_formula_biometeorologi_unggas(): void
    {
        // Uji kondisi 1: Suhu 24°C, Kelembaban 60%
        // THI = 0.8*24 + 0.60*(24 - 14.4) + 46.4 = 19.2 + 5.76 + 46.4 = 71.36 -> round 71.4 (Zona Nyaman)
        $thi1 = $this->service->calculateThi(24.0, 60.0);
        $this->assertEquals(71.4, $thi1);

        // Uji kondisi 2: Suhu 32°C, Kelembaban 70%
        // THI = 0.8*32 + 0.70*(32 - 14.4) + 46.4 = 25.6 + 12.32 + 46.4 = 84.32 -> round 84.3 (Bahaya Ekstrem)
        $thi2 = $this->service->calculateThi(32.0, 70.0);
        $this->assertEquals(84.3, $thi2);
    }

    public function test_prioritaskan_sensor_fisik_iot_jika_aktif_dan_segar(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang IoT Blitar',
            'lokasi_kota' => 'Blitar',
            'sensor_suhu' => 31.0,
            'sensor_kelembaban' => 70.0,
            'sensor_updated_at' => now()->subSeconds(20),
        ]);

        $res = $this->service->getWeather($farm);

        $this->assertEquals('sensor', $res['sumber']);
        $this->assertEquals(31.0, $res['suhu']);
        $this->assertEquals(70.0, $res['kelembaban']);
        $this->assertEquals('danger', $res['thi_level']);
        $this->assertStringContainsString('Kritis', $res['thi_kategori']);
        $this->assertNotEmpty($res['saran_manajemen']);
    }

    public function test_panggil_open_meteo_jika_tidak_ada_sensor_iot(): void
    {
        Http::fake([
            'https://api.bmkg.go.id/*' => Http::response(null, 500),
            'https://api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 28.5,
                    'relative_humidity_2m' => 65.0,
                    'apparent_temperature' => 31.0,
                    'weather_code' => 0,
                    'wind_speed_10m' => 9.2,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Surabaya',
            'lokasi_kota' => 'Surabaya',
            'latitude' => -7.2575,
            'longitude' => 112.7521,
        ]);

        $res = $this->service->getWeather($farm);

        $this->assertEquals('open_meteo', $res['sumber']);
        $this->assertEquals(28.5, $res['suhu']);
        $this->assertEquals(65.0, $res['kelembaban']);
        $this->assertEquals('Cerah', $res['cuaca_teks']);
        $this->assertNotNull($res['thi']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.open-meteo.com');
        });
    }

    public function test_fallback_berjalan_mulus_saat_koneksi_api_gagal(): void
    {
        Http::fake([
            'https://api.bmkg.go.id/*' => Http::response(null, 500),
            'https://api.open-meteo.com/*' => Http::response(null, 500),
        ]);

        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Malang',
            'lokasi_kota' => 'Malang',
        ]);

        $res = $this->service->getWeather($farm);

        $this->assertEquals('estimasi_lokal', $res['sumber']);
        $this->assertIsNumeric($res['suhu']);
        $this->assertGreaterThan(20, $res['suhu']);
        $this->assertNotEmpty($res['saran_manajemen']);
    }
}
