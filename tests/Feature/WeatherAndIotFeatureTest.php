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

    public function test_status_sensor_live_endpoint_mengembalikan_data_realtime_dan_status_online(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Realtime',
            'lokasi_kota' => 'Malang',
            'sensor_suhu' => 32.5,
            'sensor_kelembaban' => 70.0,
            'sensor_updated_at' => now(),
        ]);

        $res = $this->getJson(route('api.kandang.sensor.live', $farm));

        $res->assertStatus(200);
        $res->assertJson([
            'ok' => true,
            'suhu' => 32.5,
            'kelembaban' => 70.0,
            'sensor_aktif' => true,
            'sensor_online' => true,
            'sumber' => 'sensor',
        ]);
    }

    public function test_putus_sensor_iot_melepas_sensor_dan_beralih_ke_cuaca_bmkg(): void
    {
        Http::fake([
            'https://api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 29.5,
                    'relative_humidity_2m' => 65.0,
                    'apparent_temperature' => 32.0,
                    'weather_code' => 0,
                    'wind_speed_10m' => 6.0,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Disconnect',
            'lokasi_kota' => 'Malang',
            'sensor_suhu' => 35.0,
            'sensor_kelembaban' => 80.0,
            'sensor_updated_at' => now(),
        ]);

        $res = $this->postJson(route('api.kandang.sensor.putus', $farm));

        $res->assertStatus(200);
        $res->assertJson([
            'ok' => true,
            'sumber' => 'open_meteo',
            'suhu' => 29.5,
        ]);

        // Verifikasi endpoint live kini mengembalikan offline
        $liveRes = $this->getJson(route('api.kandang.sensor.live', $farm));
        $liveRes->assertStatus(200);
        $liveRes->assertJson([
            'ok' => true,
            'sensor_aktif' => false,
            'sensor_online' => false,
            'sumber' => 'open_meteo',
        ]);
    }

    public function test_endpoint_acuan_bmkg_live_mengembalikan_suhu_terkini_untuk_esp32(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Sidoarjo Mandiri',
            'lokasi_kota' => 'Sidoarjo',
            'latitude' => -7.4478,
            'longitude' => 112.7183,
        ]);

        $res = $this->getJson(route('api.kandang.bmkg', $farm));

        $res->assertStatus(200);
        $res->assertJsonStructure([
            'ok',
            'kota',
            'suhu',
            'kelembaban',
            'cuaca_teks',
            'jam_server',
        ]);
        $this->assertTrue($res->json('ok'));
        $this->assertEquals('Sidoarjo', $res->json('kota'));
        $this->assertIsNumeric($res->json('suhu'));
        $this->assertIsNumeric($res->json('kelembaban'));
    }
}

