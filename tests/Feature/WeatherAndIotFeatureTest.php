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

    public function test_lokasi_sentra_kandang_paten_dan_tidak_dapat_diubah(): void
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
        $res->assertSessionHas('err');
        // Lokasi tetap Blitar (tidak berubah karena paten terikat ESP32)
        $this->assertDatabaseHas('farms', [
            'id' => $farm->id,
            'lokasi_kota' => 'Blitar',
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

    public function test_toggle_controller_siap_berubah_dari_merah_ke_biru_dan_sebaliknya(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Otomasi',
            'lokasi_kota' => 'Sidoarjo',
            'controller_siap' => false,
        ]);

        // Awalnya: Belum Siap (Merah)
        $this->assertFalse($farm->controller_siap);

        // 1. Klik di Web: Menjadi Siap (Biru)
        $res = $this->postJson(route('api.kandang.kontrol.toggle-siap', $farm));
        $res->assertStatus(200);
        $res->assertJson([
            'ok' => true,
            'kontrol' => [
                'siap' => true,
            ],
        ]);
        $this->assertTrue($farm->fresh()->controller_siap);

        // 2. Klik lagi di Web: Kembali ke Standby (Merah)
        $res2 = $this->postJson(route('api.kandang.kontrol.toggle-siap', $farm));
        $res2->assertStatus(200);
        $res2->assertJson([
            'ok' => true,
            'kontrol' => [
                'siap' => false,
            ],
        ]);
        $this->assertFalse($farm->fresh()->controller_siap);
    }

    public function test_ubah_sakelar_manual_aktuator_berfungsi(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Manual Control',
            'lokasi_kota' => 'Blitar',
            'controller_siap' => false,
            'relay_kipas' => false,
        ]);

        $res = $this->postJson(route('api.kandang.kontrol.sakelar', $farm), [
            'alat' => 'kipas',
            'status' => true,
        ]);

        $res->assertStatus(200);
        $res->assertJson([
            'ok' => true,
            'kontrol' => [
                'siap' => true,
                'relay_kipas' => true,
            ],
        ]);

        $this->assertTrue($farm->fresh()->relay_kipas);
    }

    public function test_halaman_kontroler_dapat_diakses_oleh_user_yang_sudah_login_dan_memiliki_kandang(): void
    {
        Http::fake([
            'https://api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 30.0,
                    'relative_humidity_2m' => 70.0,
                    'apparent_temperature' => 33.0,
                    'weather_code' => 1,
                    'wind_speed_10m' => 8.0,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Kontroler Hub',
            'lokasi_kota' => 'Sidoarjo',
            'controller_siap' => true,
            'controller_mode' => 'otomatis',
            'relay_kipas' => true,
        ]);

        $res = $this->actingAs($user)->get(route('kontroler.index'));

        $res->assertStatus(200);
        $res->assertSee('Pusat Kendali Aktuator Kandang');
        $res->assertSee('Kipas Sirkulasi');
        $res->assertSee('Pompa Misting');
        $res->assertSee('Lampu Fotoperiode');
        $res->assertSee('Kereta Pakan Rel');
    }

    public function test_halaman_kontroler_mengarahkan_guest_ke_halaman_login(): void
    {
        $res = $this->get(route('kontroler.index'));
        $res->assertRedirect(route('masuk'));
    }

    public function test_simpan_setting_otomatisasi_kapan_alat_nyala_dan_berhenti(): void
    {
        Http::fake([
            'https://api.open-meteo.com/*' => Http::response([
                'current' => [
                    'temperature_2m' => 32.0,
                    'relative_humidity_2m' => 68.0,
                    'apparent_temperature' => 35.0,
                    'weather_code' => 0,
                    'wind_speed_10m' => 5.0,
                ],
            ], 200),
        ]);

        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Auto Settings',
            'lokasi_kota' => 'Sidoarjo',
            'controller_siap' => true,
        ]);

        $payload = [
            'kipas' => [
                'otomatis' => 1,
                'suhu_nyala' => 30.5,
                'thi_nyala' => 77.5,
            ],
            'misting' => [
                'otomatis' => 1,
                'thi_nyala' => 79.0,
                'rh_maksimal' => 72.0,
            ],
            'lampu' => [
                'otomatis' => 1,
                'jam_pagi_mulai' => '04:30',
                'jam_pagi_selesai' => '06:30',
                'jam_sore_mulai' => '17:30',
                'jam_sore_selesai' => '20:30',
            ],
        ];

        $res = $this->postJson(route('api.kandang.kontrol.settings', $farm), $payload);

        $res->assertStatus(200);
        $res->assertJson([
            'ok' => true,
            'pesan' => 'Pengaturan otomatisasi alat berhasil disimpan.',
        ]);

        $saved = $farm->fresh()->getSettingsKontroler();
        $this->assertEquals(30.5, $saved['kipas']['suhu_nyala']);
        $this->assertEquals(77.5, $saved['kipas']['thi_nyala']);
        $this->assertEquals(72.0, $saved['misting']['rh_maksimal']);
        $this->assertEquals('04:30', $saved['lampu']['jam_pagi_mulai']);
    }

    public function test_esp32_baru_dicolokkan_otomatis_mereset_sakelar_manual_ke_mati(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Boot Reset',
            'lokasi_kota' => 'Surabaya',
            'controller_siap' => true,
            'relay_kipas' => true,
            'relay_misting' => true,
            'relay_lampu' => true,
            'relay_pakan' => true,
            'sensor_updated_at' => now()->subSeconds(5),
        ]);

        // 1. Saat ESP32 baru nyala (boot = true), semua sakelar manual wajib direset ke OFF
        $res = $this->postJson(route('api.kandang.sensor', $farm), [
            'suhu' => 29.5,
            'kelembaban' => 62.0,
            'boot' => true,
        ]);

        $res->assertStatus(200);
        $res->assertJson([
            'ok' => true,
            'kontrol' => [
                'relay_kipas' => false,
                'relay_misting' => false,
                'relay_lampu' => false,
                'relay_pakan' => false,
            ],
        ]);

        // 2. Saat sakelar manual dinyalakan lalu ada jeda WiFi Hotspot 25 detik (tanpa boot), sakelar TIDAK boleh mati sendiri
        $farm->refresh()->update([
            'relay_kipas' => true,
            'relay_misting' => true,
            'sensor_updated_at' => now()->subSeconds(25),
        ]);

        $resWifiJitter = $this->postJson(route('api.kandang.sensor', $farm), [
            'suhu' => 29.5,
            'kelembaban' => 62.0,
        ]);

        $resWifiJitter->assertStatus(200);
        $resWifiJitter->assertJson([
            'ok' => true,
            'kontrol' => [
                'relay_kipas' => true,
                'relay_misting' => true,
            ],
        ]);
    }

    public function test_klik_sakelar_manual_otomatis_mematikan_otomatisasi_agar_tidak_ditimpa_sensor(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Anti Konflik Otomasi',
            'lokasi_kota' => 'Surabaya',
            'controller_siap' => true,
            'relay_kipas' => false,
            'relay_misting' => false,
            'sensor_suhu' => 36.3,
            'sensor_kelembaban' => 54.9,
            'sensor_updated_at' => now(),
            'controller_settings' => [
                'kipas' => ['otomatis' => true, 'suhu_nyala' => 37.0, 'thi_nyala' => 90.0],
                'misting' => ['otomatis' => true, 'thi_nyala' => 90.0, 'rh_maksimal' => 61.0],
                'lampu' => ['otomatis' => false, 'jam_pagi_mulai' => '04:00', 'jam_pagi_selesai' => '07:00', 'jam_sore_mulai' => '18:00', 'jam_sore_selesai' => '21:00'],
                'pakan' => ['otomatis' => false, 'mode' => 'shuttle', 'durasi_detik' => 4, 'jam_pagi' => '07:00', 'jam_sore' => '15:30'],
            ],
        ]);

        // 1. Klik manual ON saat suhu (36.3) di bawah ambang otomatis (37.0) -> otomatis dimatikan & alat tetap ON saat ESP32 melapor
        $this->postJson(route('api.kandang.kontrol.sakelar', $farm), [
            'alat' => 'kipas',
            'status' => true,
        ])->assertOk()->assertJsonPath('kontrol.relay_kipas', true)->assertJsonPath('kontrol.settings.kipas.otomatis', false);

        $this->postJson(route('api.kandang.kontrol.sakelar', $farm), [
            'alat' => 'misting',
            'status' => true,
        ])->assertOk()->assertJsonPath('kontrol.relay_misting', true)->assertJsonPath('kontrol.settings.misting.otomatis', false);

        $resEsp = $this->postJson(route('api.kandang.sensor', $farm), [
            'suhu' => 36.3,
            'kelembaban' => 54.9,
        ]);
        $resEsp->assertOk()
            ->assertJsonPath('kontrol.relay_kipas', true)
            ->assertJsonPath('kontrol.relay_misting', true);

        // 2. Sebaliknya: jika otomatisasi aktif menahan alat ON (suhu 36.3 >= 31.0), klik manual OFF wajib mematikan alat secara paten
        $settings = $farm->fresh()->getSettingsKontroler();
        $settings['kipas']['otomatis'] = true;
        $settings['kipas']['suhu_nyala'] = 31.0;
        $farm->update(['controller_settings' => $settings, 'relay_kipas' => true]);

        $this->postJson(route('api.kandang.kontrol.sakelar', $farm), [
            'alat' => 'kipas',
            'status' => false,
        ])->assertOk()->assertJsonPath('kontrol.relay_kipas', false)->assertJsonPath('kontrol.settings.kipas.otomatis', false);

        $resEspOff = $this->postJson(route('api.kandang.sensor', $farm), [
            'suhu' => 36.3,
            'kelembaban' => 54.9,
        ]);
        $resEspOff->assertOk()->assertJsonPath('kontrol.relay_kipas', false);
    }

    public function test_lampu_fotoperiode_sinkron_manual_dan_jadwal_otomatis_ke_esp32(): void
    {
        $user = User::factory()->create();
        $farm = Farm::create([
            'user_id' => $user->id,
            'nama' => 'Kandang Lampu Fotoperiode',
            'lokasi_kota' => 'Surabaya',
            'controller_siap' => false,
            'relay_lampu' => false,
            'sensor_suhu' => 29.0,
            'sensor_kelembaban' => 65.0,
            'sensor_updated_at' => now(),
        ]);

        // 1. Sakelar Manual Lampu Fotoperiode ON -> terkirim true ke ESP32 dan tidak mati sendiri
        $this->postJson(route('api.kandang.kontrol.sakelar', $farm), [
            'alat' => 'lampu',
            'status' => true,
        ])->assertOk()->assertJsonPath('kontrol.relay_lampu', true);

        $this->postJson(route('api.kandang.sensor', $farm), [
            'suhu' => 29.0,
            'kelembaban' => 65.0,
        ])->assertOk()->assertJsonPath('kontrol.relay_lampu', true);

        // 2. Jadwal Otomatisasi Cerdas Fotoperiode (termasuk rentang lintas tengah malam 22:00 - 02:00)
        \Illuminate\Support\Carbon::setTestNow(\Illuminate\Support\Carbon::create(2026, 11, 8, 23, 15, 0, 'Asia/Jakarta'));

        $farm->refresh()->update([
            'sensor_updated_at' => now(),
            'controller_settings' => [
                'kipas' => ['otomatis' => false, 'suhu_nyala' => 31.0, 'thi_nyala' => 78.0],
                'misting' => ['otomatis' => false, 'thi_nyala' => 80.0, 'rh_maksimal' => 75.0],
                'lampu' => [
                    'otomatis' => true,
                    'jam_pagi_mulai' => '04:00',
                    'jam_pagi_selesai' => '07:00',
                    'jam_sore_mulai' => '22:00',
                    'jam_sore_selesai' => '02:00',
                ],
                'pakan' => ['otomatis' => false, 'mode' => 'shuttle', 'durasi_detik' => 4, 'jam_pagi' => '07:00', 'jam_sore' => '15:30'],
            ],
            'relay_lampu' => false,
        ]);

        $this->postJson(route('api.kandang.sensor', $farm), [
            'suhu' => 29.0,
            'kelembaban' => 65.0,
        ])->assertOk()->assertJsonPath('kontrol.relay_lampu', true);

        // 3. Klik Sakelar Manual OFF saat jadwal otomatis sedang aktif -> otomatisasi lampu dimatikan & lampu tetap OFF
        $this->postJson(route('api.kandang.kontrol.sakelar', $farm), [
            'alat' => 'lampu',
            'status' => false,
        ])->assertOk()
            ->assertJsonPath('kontrol.relay_lampu', false)
            ->assertJsonPath('kontrol.settings.lampu.otomatis', false);

        \Illuminate\Support\Carbon::setTestNow();
    }
}

