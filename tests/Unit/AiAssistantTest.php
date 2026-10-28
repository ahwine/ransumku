<?php

namespace Tests\Unit;

use App\Models\Farm;
use App\Services\AiAssistant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    public function test_tersedia_mengikuti_konfigurasi_kunci(): void
    {
        Config::set('ransumku.gemini.key', null);
        $ai = new AiAssistant();
        $this->assertFalse($ai->tersedia());

        Config::set('ransumku.gemini.key', 'AIzaSyFakeTestKey123');
        $this->assertTrue($ai->tersedia());
    }

    public function test_fallback_berjalan_saat_kunci_kosong(): void
    {
        Config::set('ransumku.gemini.key', null);
        $ai = new AiAssistant();

        $farm = new Farm(['nama' => 'Kandang Tes', 'jumlah_ayam' => 100, 'fase' => 'Layer']);

        $jawaban = $ai->jawab($farm, 'Apa pakan pengganti jagung?');
        $this->assertStringContainsString('kunci GEMINI_API_KEY belum dipasang', $jawaban);
        $this->assertStringContainsString('sorgum', $jawaban);
    }

    public function test_panggilan_gemini_berhasil_mengembalikan_teks_ai(): void
    {
        Config::set('ransumku.gemini.key', 'AIzaSyFakeKey123');
        Config::set('ransumku.gemini.model', 'gemini-flash-lite-latest');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Halo peternak! Berdasarkan data kandang Anda, produksi telur berada pada kondisi baik.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $ai = new AiAssistant();
        $farm = new Farm(['nama' => 'Kandang Tes', 'jumlah_ayam' => 500, 'fase' => 'Layer']);

        $jawaban = $ai->jawab($farm, 'Bagaimana kondisi ayam saya?');

        $this->assertEquals('Halo peternak! Berdasarkan data kandang Anda, produksi telur berada pada kondisi baik.', $jawaban);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-goog-api-key', 'AIzaSyFakeKey123')
                && str_contains($request->url(), 'gemini-flash-lite-latest')
                && isset($request['system_instruction'])
                && isset($request['contents']);
        });
    }

    public function test_fallback_saat_api_gemini_error(): void
    {
        Config::set('ransumku.gemini.key', 'AIzaSyFakeKey123');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response(['error' => 'Quota Exceeded'], 429),
        ]);

        $ai = new AiAssistant();
        $farm = new Farm(['nama' => 'Kandang Tes', 'jumlah_ayam' => 500, 'fase' => 'Layer']);

        $jawaban = $ai->jawab($farm, 'Bagaimana kondisi ayam saya?');

        $this->assertStringContainsString('gangguan koneksi ke server', $jawaban);
    }

    public function test_multi_turn_history_diformat_dengan_role_bergantian(): void
    {
        Config::set('ransumku.gemini.key', 'AIzaSyFakeKey123');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [['text' => 'Bahan sorgum bisa menjadi alternatif bagus untuk jagung.']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $ai = new AiAssistant();
        $farm = new Farm(['nama' => 'Kandang Tes', 'jumlah_ayam' => 500, 'fase' => 'Layer']);

        $riwayat = collect([
            (object) ['role' => 'user', 'isi' => 'Halo'],
            (object) ['role' => 'assistant', 'isi' => 'Halo! Ada yang bisa dibantu?'],
        ]);

        $jawaban = $ai->jawab($farm, 'Bagaimana dengan sorgum?', $riwayat);

        $this->assertEquals('Bahan sorgum bisa menjadi alternatif bagus untuk jagung.', $jawaban);

        Http::assertSent(function ($request) {
            $contents = $request['contents'];
            return count($contents) === 3
                && $contents[0]['role'] === 'user'
                && $contents[0]['parts'][0]['text'] === 'Halo'
                && $contents[1]['role'] === 'model'
                && $contents[1]['parts'][0]['text'] === 'Halo! Ada yang bisa dibantu?'
                && $contents[2]['role'] === 'user'
                && $contents[2]['parts'][0]['text'] === 'Bagaimana dengan sorgum?';
        });
    }
}

