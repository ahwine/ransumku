<?php

namespace Tests\Unit;

use App\Models\Farm;
use App\Services\AiAssistant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ransumku.openrouter.key', null);
        Config::set('ransumku.gemini.key', null);
    }

    public function test_tersedia_mengikuti_konfigurasi_kunci(): void
    {
        $ai = new AiAssistant();
        $this->assertFalse($ai->tersedia());

        Config::set('ransumku.openrouter.key', 'sk-or-v1-fake-test-key');
        $this->assertTrue($ai->tersedia());

        Config::set('ransumku.openrouter.key', null);
        Config::set('ransumku.gemini.key', 'AIzaSyFakeTestKey123');
        $this->assertTrue($ai->tersedia());
    }

    public function test_fallback_berjalan_saat_kunci_kosong(): void
    {
        $ai = new AiAssistant();
        $farm = new Farm(['nama' => 'Kandang Tes', 'jumlah_ayam' => 100, 'fase' => 'Layer']);

        $jawaban = $ai->jawab($farm, 'Apa pakan pengganti jagung?');
        $this->assertStringContainsString('kunci OPENROUTER_API_KEY belum dipasang', $jawaban);
        $this->assertStringContainsString('sorgum', $jawaban);
    }

    public function test_panggilan_openrouter_berhasil_mengembalikan_teks_ai(): void
    {
        Config::set('ransumku.openrouter.key', 'sk-or-v1-fake-key-123');
        Config::set('ransumku.openrouter.model', 'google/gemma-4-31b-it:free');
        Config::set('ransumku.openrouter.fallback_models', ['openrouter/free']);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Halo peternak! Berdasarkan data kandang Anda, performa produksi telur sangat baik.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $ai = new AiAssistant();
        $farm = new Farm(['nama' => 'Kandang Tes', 'jumlah_ayam' => 500, 'fase' => 'Layer']);

        $jawaban = $ai->jawab($farm, 'Bagaimana kondisi ayam saya?');

        $this->assertEquals('Halo peternak! Berdasarkan data kandang Anda, performa produksi telur sangat baik.', $jawaban);

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer sk-or-v1-fake-key-123')
                && $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $request['model'] === 'google/gemma-4-31b-it:free'
                && $request['models'] === ['google/gemma-4-31b-it:free', 'openrouter/free']
                && isset($request['messages'])
                && $request['messages'][0]['role'] === 'system'
                && $request['messages'][1]['role'] === 'user';
        });
    }

    public function test_multi_turn_history_openrouter_diformat_dengan_role_bergantian(): void
    {
        Config::set('ransumku.openrouter.key', 'sk-or-v1-fake-key-123');
        Config::set('ransumku.openrouter.model', 'openrouter/free');
        Config::set('ransumku.openrouter.fallback_models', ['openrouter/free']);

        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Sorgum dapat menggantikan sebagian jagung dengan aman.',
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

        $this->assertEquals('Sorgum dapat menggantikan sebagian jagung dengan aman.', $jawaban);

        Http::assertSent(function ($request) {
            $messages = $request['messages'];

            return count($messages) === 4
                && ! isset($request['models'])
                && $messages[0]['role'] === 'system'
                && $messages[1]['role'] === 'user'
                && $messages[1]['content'] === 'Halo'
                && $messages[2]['role'] === 'assistant'
                && $messages[2]['content'] === 'Halo! Ada yang bisa dibantu?'
                && $messages[3]['role'] === 'user'
                && $messages[3]['content'] === 'Bagaimana dengan sorgum?';
        });
    }

    public function test_fallback_saat_api_openrouter_error(): void
    {
        Config::set('ransumku.openrouter.key', 'sk-or-v1-fake-key-123');

        Http::fake([
            'https://openrouter.ai/api/v1/*' => Http::response(['error' => ['message' => 'Rate limit exceeded']], 429),
        ]);

        $ai = new AiAssistant();
        $farm = new Farm(['nama' => 'Kandang Tes', 'jumlah_ayam' => 500, 'fase' => 'Layer']);

        $jawaban = $ai->jawab($farm, 'Bagaimana kondisi ayam saya?');

        $this->assertStringContainsString('gangguan koneksi ke server', $jawaban);
    }

    public function test_panggilan_gemini_cadangan_tetap_berfungsi(): void
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

        $riwayat = collect([
            (object) ['role' => 'user', 'isi' => 'Halo'],
            (object) ['role' => 'assistant', 'isi' => 'Halo! Ada yang bisa dibantu?'],
        ]);

        $jawaban = $ai->jawab($farm, 'Bagaimana kondisi ayam saya?', $riwayat);

        $this->assertEquals('Halo peternak! Berdasarkan data kandang Anda, produksi telur berada pada kondisi baik.', $jawaban);

        Http::assertSent(function ($request) {
            $contents = $request['contents'];

            return $request->hasHeader('x-goog-api-key', 'AIzaSyFakeKey123')
                && str_contains($request->url(), 'gemini-flash-lite-latest')
                && isset($request['system_instruction'])
                && count($contents) === 3
                && $contents[0]['role'] === 'user'
                && $contents[1]['role'] === 'model'
                && $contents[2]['role'] === 'user';
        });
    }
}

