<?php

namespace Tests\Feature;

use App\Models\Farm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherAndIotFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_cuaca_dan_indeks_thi(): void
    {
        Http::fake([
            'https://api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 32.0,
                    'relative_humidity_2m' => 75.0,
                    'apparent_temperature' => 36.0,
                    'weather_code' => 1,
                    'wind_speed_10m' => 7.5,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Layer Blitar',
            'lokasi_kota' => 'Blitar',
            'jumlah_ayam' => 500,
            'fase' => 'produksi',
        ]);

        $res = $this->actingAs($user)->get(route('beranda'));

        $res->assertStatus(200);
        $res->assertSee('Blitar');
        $res->assertSee('32°C');
        $res->assertSee('THI');
        $res->assertSee('Stres Panas');
    }

    public function test_peternak_bisa_mengubah_lokasi_kandang(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Blitar',
            'lokasi_kota' => 'Blitar',
            'jumlah_ayam' => 300,
        ]);

        $res = $this->actingAs($user)->post(route('kandang.lokasi'), [
            'kota' => 'Boyolali',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('farms', [
            'id' => $farm->id,
            'lokasi_kota' => 'Boyolali',
            'latitude' => -7.5333,
            'longitude' => 110.6000,
        ]);
    }

    public function test_api_webhook_iot_menerima_data_sensor_suhu_dan_kelembaban(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Modern Sidrap',
            'lokasi_kota' => 'Sidrap',
            'jumlah_ayam' => 1000,
            'iot_token' => 'secret-iot-token-123',
        ]);

        // Kirim data tanpa token sah -> Ditolak 403
        $badRes = $this->postJson(route('api.kandang.sensor', $farm), [
            'suhu' => 31.5,
            'kelembaban' => 70.0,
            'token' => 'wrong-token',
        ]);
        $badRes->assertStatus(403);

        // Kirim data dengan token sah -> Berhasil 200
        $okRes = $this->postJson(route('api.kandang.sensor', $farm), [
            'suhu' => 34.0,
            'kelembaban' => 72.0,
            'token' => 'secret-iot-token-123',
        ]);

        $okRes->assertStatus(200);
        $okRes->assertJson([
            'ok' => true,
            'suhu' => 34.0,
            'kelembaban' => 72.0,
        ]);

        $this->assertDatabaseHas('farms', [
            'id' => $farm->id,
            'sensor_suhu' => 34.0,
            'sensor_kelembaban' => 72.0,
        ]);
    }
}

