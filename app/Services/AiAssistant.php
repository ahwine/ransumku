<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\Ration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asisten AI RansumKu (didukung OpenRouter API dengan dukungan cadangan Google Gemini API).
 *
 * Berperan sebagai asisten AI global/serbabisa sekaligus konsultan ahli peternakan ayam petelur,
 * pakan/ransum, IoT, bisnis, dan pengetahuan umum yang terhubung langsung dengan data kandang pengguna.
 * Formulasi angka optimasi pakan tetap dihitung oleh program (Linear Programming), bukan halusinasi LLM.
 * Jika API Key belum diisi atau koneksi gagal, sistem otomatis beralih ke fallback tanpa crash.
 */
class AiAssistant
{
    private const BASE_SYSTEM = <<<'SYS'
Kamu adalah "Asisten Cerdas RansumKu", asisten AI serbabisa (global) yang cerdas, komunikatif, dan terhubung langsung dengan sistem manajemen peternakan ayam petelur RansumKu di Indonesia.

PANDUAN KERJA UTAMA:
1. CAKUPAN GLOBAL & TANPA PENOLAKAN TOPIK (NO TOPIC RESTRICTION):
   - Kamu BEBAS dan WAJIB membantu menjawab pertanyaan APA SAJA dari pengguna secara langsung dan informatif—baik seputar peternakan ayam, formulasi pakan, harga pasar & ekonomi, bisnis, IoT/elektronika, pemrograman, matematika, pengetahuan umum, tokoh/pemerintahan, sains, maupun topik sehari-hari lainnya.
   - JANGAN PERNAH menolak pertanyaan dengan alasan "di luar topik peternakan" atau berkata "Mohon maaf, saya khusus menangani data kandang...". Jawablah setiap pertanyaan sebaik mungkin layaknya asisten AI global yang pintar dan membantu.

2. PEMANFAATAN DATA KANDANG SECARA CERDAS:
   - Di bawah instruksi ini dilampirkan [DATA KANDANG PENGGUNA TERKINI].
   - Jika pertanyaan berkaitan dengan kandang, ayam, produksi telur, pakan/ransum, stok bahan, biaya/keuntungan, atau suhu/cuaca kandang pengguna, gunakan angka nyata dari [DATA KANDANG PENGGUNA TERKINI] secara akurat.
   - Jika pertanyaan bersifat umum (tidak ada hubungannya dengan kandang pengguna), jawab langsung ke intinya secara natural tanpa perlu memaksakan pembahasan kandang.

3. PERTANYAAN HARGA PASAR & INFORMASI TERKINI:
   - Jika pengguna menanyakan harga pasar (misalnya harga telur, jagung, dedak, konsentrat saat ini), JANGAN menolak! Sebutkan harga acuan yang tercatat di sistem RansumKu pengguna serta kisaran harga pasar umum di Indonesia (tingkat peternak/kandang vs eceran konsumen), lalu beri catatan ringan bahwa harga harian dapat sedikit berbeda antar daerah (mengacu pada update Pinsar Petelur Nasional / Panel Harga Pangan Bapanas).

4. PANDUAN KESEHATAN UNGGAS & FORMULASI PAKAN:
   - Untuk keluhan kesehatan ayam, berikan analisis kemungkinan penyebab dan langkah penanganan awal/biosekuriti yang praktis, serta sarankan pemeriksaan dokter hewan bila gejala berat atau membutuhkan resep obat keras/antibiotik.
   - Untuk hitungan persentase formulasi ransum paling hemat secara eksak, ingatkan pengguna bahwa mereka juga bisa memasukkan bahan ke menu 'Daftar Bahan' lalu menekan 'Hitung Ulang' di menu Ransum.

5. GAYA PENYAMPAIAN:
   - Gunakan Bahasa Indonesia yang ramah, luwes, lugas, dan mudah dipahami (atau sesuaikan bahasa jika pengguna meminta bahasa lain).
   - Gunakan pemformatan Markdown yang rapi (poin tebal, daftar bernomor, atau tabel ringkas bila perlu).
   - Susun jawaban secara padat, jelas, dan pastikan seluruh penjelasan selesai tuntas hingga kalimat penutup tanpa terpotong.
SYS;

    /**
     * Memeriksa apakah integrasi AI aktif (kunci OpenRouter atau Gemini terkonfigurasi).
     */
    public function tersedia(): bool
    {
        return $this->menggunakanOpenRouter() || $this->menggunakanGemini();
    }

    /**
     * Memeriksa apakah kunci API OpenRouter tersedia.
     */
    private function menggunakanOpenRouter(): bool
    {
        return filled(config('ransumku.openrouter.key'));
    }

    /**
     * Memeriksa apakah kunci API Google Gemini tersedia.
     */
    private function menggunakanGemini(): bool
    {
        return filled(config('ransumku.gemini.key'));
    }

    /**
     * Menjawab pertanyaan peternak dengan menyertakan konteks kandang & riwayat percakapan.
     *
     * @param  iterable<int, object>  $riwayat
     */
    public function jawab(Farm $farm, string $pertanyaan, iterable $riwayat = []): string
    {
        $konteks = $this->konteks($farm);
        $systemInstruction = self::BASE_SYSTEM
            ."\n\n### DATA KANDANG PENGGUNA TERKINI (Fakta aktual dari sistem RansumKu):\n"
            .$konteks;

        return $this->panggilChat($pertanyaan, $riwayat, $systemInstruction) ?? $this->fallbackJawab($pertanyaan);
    }

    /**
     * Menjelaskan hasil formulasi ransum pakan yang telah dihitung oleh sistem.
     */
    public function jelaskan(Ration $ration): string
    {
        $ration->loadMissing('items', 'standard');
        $prompt = "Jelaskan dalam 4-6 kalimat sederhana dan ramah mengapa komposisi ransum ini dipilih, memakai HANYA data dan angka berikut:\n"
            .$this->ringkasRansum($ration);

        return $this->panggilChat($prompt, [], self::BASE_SYSTEM) ?? $this->fallbackJelaskan($ration);
    }

    /**
     * Mengirim permintaan percakapan ke penyedia AI aktif (OpenRouter utama, Gemini cadangan).
     *
     * @param  iterable<int, object>  $riwayat
     */
    private function panggilChat(string $pertanyaanSekarang, iterable $riwayat = [], ?string $systemInstruction = null): ?string
    {
        if (! $this->tersedia()) {
            return null;
        }

        if ($this->menggunakanOpenRouter()) {
            $messages = $this->bangunPesanOpenRouter($pertanyaanSekarang, $riwayat, $systemInstruction);
            $jawaban = $this->panggilOpenRouter($messages);

            if ($jawaban !== null) {
                return $jawaban;
            }
        }

        if ($this->menggunakanGemini()) {
            $contents = $this->bangunPercakapan($pertanyaanSekarang, $riwayat);

            return $this->panggilGemini($contents, $systemInstruction);
        }

        return null;
    }

    /**
     * Memanggil OpenRouter Chat Completions API (kompatibel standar OpenAI).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    private function panggilOpenRouter(array $messages): ?string
    {
        try {
            $cfg = config('ransumku.openrouter');
            $baseUrl = rtrim((string) ($cfg['base_url'] ?? 'https://openrouter.ai/api/v1'), '/');
            $model = (string) ($cfg['model'] ?? 'openrouter/free');
            $fallbackModels = is_array($cfg['fallback_models'] ?? null) ? $cfg['fallback_models'] : [];
            $models = array_slice(array_values(array_unique(array_filter([$model, ...$fallbackModels]))), 0, 3);
            $maxTokens = (int) ($cfg['max_tokens'] ?? 8192);
            $timeout = (int) ($cfg['timeout'] ?? 120);

            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => $maxTokens,
            ];

            if (count($models) > 1) {
                $payload['models'] = $models;
            }

            $res = Http::withoutVerifying()
                ->timeout($timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$cfg['key'],
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => (string) config('app.url', 'http://localhost'),
                    'X-Title' => (string) config('app.name', 'RansumKu'),
                ])
                ->post("{$baseUrl}/chat/completions", $payload);

            if (! $res->successful()) {
                Log::warning('OpenRouter API call gagal: HTTP '.$res->status().' - '.$res->body());

                return null;
            }

            $teks = trim((string) data_get($res->json(), 'choices.0.message.content', ''));

            return $teks !== '' ? $teks : null;
        } catch (\Throwable $e) {
            Log::warning('OpenRouter API exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Memanggil Google Gemini REST API dengan payload multi-turn dan system instruction.
     *
     * @param  array<int, array{role: string, parts: array<int, array{text: string}>}>  $contents
     */
    private function panggilGemini(array $contents, ?string $systemInstruction = null): ?string
    {
        if (! $this->menggunakanGemini()) {
            return null;
        }

        try {
            $cfg = config('ransumku.gemini');
            $model = $cfg['model'] ?? 'gemini-3.5-flash-lite';
            $timeout = (int) ($cfg['timeout'] ?? 60);

            $payload = [
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 800,
                ],
            ];

            if ($systemInstruction) {
                $payload['system_instruction'] = [
                    'parts' => [['text' => $systemInstruction]],
                ];
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

            $res = Http::withoutVerifying()
                ->timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $cfg['key'],
                ])
                ->post($url, $payload);

            if (! $res->successful()) {
                Log::warning('Gemini API call gagal: HTTP '.$res->status().' - '.$res->body());

                return null;
            }

            $teks = trim((string) data_get($res->json(), 'candidates.0.content.parts.0.text', ''));

            return $teks !== '' ? $teks : null;
        } catch (\Throwable $e) {
            Log::warning('Gemini API exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Menyusun riwayat percakapan agar sesuai dengan struktur `messages` OpenRouter / OpenAI API:
     * - Diawali `system` jika ada systemInstruction
     * - Riwayat menggunakan role `user` dan `assistant` secara bergantian
     * - Diakhiri dengan pertanyaan pengguna terkini
     *
     * @param  iterable<int, object>  $riwayat
     * @return array<int, array{role: string, content: string}>
     */
    private function bangunPesanOpenRouter(string $pertanyaanSekarang, iterable $riwayat, ?string $systemInstruction = null): array
    {
        $messages = [];

        if ($systemInstruction !== null && trim($systemInstruction) !== '') {
            $messages[] = [
                'role' => 'system',
                'content' => $systemInstruction,
            ];
        }

        $chatTurns = [];

        foreach ($riwayat as $chat) {
            $rawRole = $chat->role ?? 'user';
            $role = ($rawRole === 'assistant' || $rawRole === 'model') ? 'assistant' : 'user';
            $teks = trim((string) ($chat->isi ?? ''));

            if ($teks === '') {
                continue;
            }

            $terakhir = count($chatTurns) - 1;
            if ($terakhir >= 0 && $chatTurns[$terakhir]['role'] === $role) {
                $chatTurns[$terakhir]['content'] .= "\n".$teks;
            } else {
                if (empty($chatTurns) && $role !== 'user') {
                    continue;
                }

                $chatTurns[] = [
                    'role' => $role,
                    'content' => $teks,
                ];
            }
        }

        $terakhir = count($chatTurns) - 1;
        if ($terakhir >= 0 && $chatTurns[$terakhir]['role'] === 'user') {
            $chatTurns[$terakhir]['content'] .= "\n".$pertanyaanSekarang;
        } else {
            $chatTurns[] = [
                'role' => 'user',
                'content' => $pertanyaanSekarang,
            ];
        }

        return [...$messages, ...$chatTurns];
    }

    /**
     * Menyusun riwayat percakapan agar sesuai dengan struktur `contents` Gemini API:
     * - Hanya menggunakan role 'user' dan 'model'
     * - Role harus bergantian (tidak boleh berurutan sama)
     * - Dimulai dari 'user' dan diakhiri dengan pertanyaan pengguna terkini
     *
     * @param  iterable<int, object>  $riwayat
     * @return array<int, array{role: string, parts: array<int, array{text: string}>}>
     */
    private function bangunPercakapan(string $pertanyaanSekarang, iterable $riwayat): array
    {
        $contents = [];

        foreach ($riwayat as $chat) {
            $rawRole = $chat->role ?? 'user';
            $role = ($rawRole === 'assistant' || $rawRole === 'model') ? 'model' : 'user';
            $teks = trim((string) ($chat->isi ?? ''));

            if ($teks === '') {
                continue;
            }

            $terakhir = count($contents) - 1;
            if ($terakhir >= 0 && $contents[$terakhir]['role'] === $role) {
                // Gabungkan pesan jika berturut-turut memiliki role yang sama
                $contents[$terakhir]['parts'][0]['text'] .= "\n".$teks;
            } else {
                // Gemini mengharuskan percakapan diawali oleh 'user'
                if (empty($contents) && $role !== 'user') {
                    continue;
                }

                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $teks]],
                ];
            }
        }

        // Tambahkan pertanyaan pengguna saat ini
        $terakhir = count($contents) - 1;
        if ($terakhir >= 0 && $contents[$terakhir]['role'] === 'user') {
            $contents[$terakhir]['parts'][0]['text'] .= "\n".$pertanyaanSekarang;
        } else {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $pertanyaanSekarang]],
            ];
        }

        return $contents;
    }

    /**
     * Membangun ringkasan konteks data kandang terkini untuk diberikan ke LLM.
     */
    private function konteks(Farm $farm): string
    {
        $baris = [];
        $populasi = (int) $farm->jumlah_ayam;
        $hargaTelurAcuan = (float) config('ransumku.harga_telur_default', 27969);
        $baris[] = "- Waktu Sistem: ".now()->translatedFormat('d F Y, H:i').' WIB';
        $baris[] = "- Nama Kandang: {$farm->nama}";
        $baris[] = "- Populasi Ayam: {$populasi} ekor (Fase: {$farm->fase})";
        $baris[] = '- Acuan Harga Pasar Telur Ayam Ras di Sistem: Rp '.number_format($hargaTelurAcuan, 0, ',', '.').'/kg';

        if (! $farm->exists) {
            return implode("\n", $baris);
        }

        // Daftar bahan aktif & stok
        $bahanAktif = $farm->farmIngredients()
            ->with('ingredient')
            ->where('aktif', true)
            ->get();

        if ($bahanAktif->isNotEmpty()) {
            $daftarBahan = $bahanAktif->map(function ($fi) {
                $stokStatus = $fi->stok_kg < 50 ? ' [STOK MENIPIS]' : '';

                return "{$fi->ingredient->nama} (Rp ".number_format($fi->harga, 0, ',', '.')."/kg, stok {$fi->stok_kg} kg{$stokStatus})";
            })->implode('; ');
            $baris[] = "- Bahan Pakan Tersedia: {$daftarBahan}";
        } else {
            $baris[] = '- Bahan Pakan Tersedia: Belum ada bahan aktif yang diaktifkan.';
        }

        // Ransum terakhir
        if ($r = $farm->lastRation()) {
            $baris[] = '- Formulasi Ransum Terakhir: '.$this->ringkasRansum($r->load('items', 'standard'));
        } else {
            $baris[] = '- Formulasi Ransum Terakhir: Belum pernah menyusun ransum.';
        }

        // Catatan 7 hari terakhir & ringkasan statistik
        $logs = $farm->logs()->orderByDesc('tanggal')->limit(7)->get()->reverse()->values();
        if ($logs->isNotEmpty()) {
            $logDetail = $logs->map(fn ($l) => $l->tanggal->format('d/m').': pakan '.$l->pakan_kg.' kg, telur '.$l->telur_butir.' btr ('.round($l->telurKgEfektif(), 1).' kg), mati '.$l->ayam_mati.' ekor')->implode(' | ');
            $baris[] = "- Catatan 7 Hari Terakhir: {$logDetail}";

            $rataPakan = round($logs->avg('pakan_kg'), 1);
            $rataGramEkor = $populasi > 0 ? round(($rataPakan / $populasi) * 1000, 1) : 0;
            $rataTelur = round($logs->avg('telur_butir'), 0);
            $hdp = $populasi > 0 ? round(($rataTelur / $populasi) * 100, 1) : 0;
            $totalMati = (int) $logs->sum('ayam_mati');

            $baris[] = "- Rangkuman Performa 7 Hari: Rata-rata pakan {$rataPakan} kg/hari ({$rataGramEkor} g/ekor/hari), Rata-rata produksi telur {$rataTelur} butir/hari (Hen-Day ~{$hdp}%), Total kematian 7 hari {$totalMati} ekor.";
        } else {
            $baris[] = '- Catatan 7 Hari Terakhir: Belum ada catatan harian di Buku Kandang.';
        }

        // Peringatan dini / anomali kandang aktif
        $alerts = $farm->alerts()->where('dibaca', false)->latest('tanggal_data')->limit(3)->get();
        if ($alerts->isNotEmpty()) {
            $listAlert = $alerts->map(fn ($a) => "[{$a->level}] {$a->pesan}")->implode('; ');
            $baris[] = "- Peringatan Dini Aktif: {$listAlert}";
        } else {
            $baris[] = '- Peringatan Dini Aktif: Tidak ada anomali terdeteksi (kondisi normal).';
        }

        // Kondisi Cuaca Mikro & Indeks Stres Panas (THI) Terkini
        try {
            $weather = app(WeatherService::class)->getWeather($farm);
            $saranCuaca = implode(' ', array_slice($weather['saran_manajemen'], 0, 2));
            $baris[] = "- Cuaca Mikro & Suhu Kandang: Lokasi {$weather['kota']}, Suhu {$weather['suhu']}°C, Kelembaban {$weather['kelembaban']}%, Status THI: {$weather['thi']} ({$weather['thi_kategori']}). {$saranCuaca}";
        } catch (\Throwable $e) {
            // Abaikan kegagalan cuaca agar asisten tetap berjalan lancar
        }

        return implode("\n", $baris);
    }

    /**
     * Meringkas formulasi ransum menjadi kalimat padat informatif.
     */
    private function ringkasRansum(Ration $r): string
    {
        $items = $r->items->map(fn ($i) => "{$i->nama} {$i->persen}% ({$i->kg} kg)")->implode(', ');
        $gizi = collect($r->hasil['gizi'] ?? [])->map(fn ($g) => "{$g['label']} {$g['nilai']}{$g['satuan']} (target {$g['target']}, ".($g['ok'] ? 'terpenuhi' : 'belum').')')->implode('; ');
        $standar = $r->namaStandar();

        return "Total {$r->total_kg} kg, Biaya Rp ".number_format($r->total_biaya, 0, ',', '.')." (Rp ".number_format($r->biaya_per_kg, 0, ',', '.')."/kg). Komposisi: {$items}. Gizi: {$gizi}. Acuan: {$standar}.";
    }

    /**
     * Fallback jawaban cerdas jika API AI belum disetel atau koneksi gagal.
     */
    private function fallbackJawab(string $q): string
    {
        $q = strtolower($q);
        $statusInfo = $this->tersedia()
            ? 'Asisten AI sedang mengalami gangguan koneksi ke server.'
            : 'Asisten AI belum terhubung (kunci OPENROUTER_API_KEY belum dipasang di sistem).';

        if (str_contains($q, 'ganti') || str_contains($q, 'pengganti') || str_contains($q, 'alternatif')) {
            return "{$statusInfo}\n\nSaran pakan pengganti umum:\n- Pengganti jagung (sumber energi): sorgum, menir, atau dedak/bekatul berkualitas baik.\n- Pengganti konsentrat / sumber protein: bungkil kedelai (SBM) atau maggot BSF.\n\nSilakan masukkan bahan alternatif tersebut ke menu 'Daftar Bahan' beserta harga & stoknya, lalu tekan 'Hitung Ulang' pada menu Ransum agar program menghitung komposisi nutrisi dan biaya termurahnya.";
        }

        if (str_contains($q, 'telur') && (str_contains($q, 'turun') || str_contains($q, 'kurang'))) {
            return "{$statusInfo}\n\nProduksi telur turun dapat dipengaruhi oleh:\n1. Kualitas/kadar protein pakan menurun atau konsumsi harian berkurang.\n2. Suhu kandang terlalu panas (heat stress) atau ventilasi kurang optimal.\n3. Masuk masa rontok bulu (molting) atau gangguan kesehatan.\n\nPeriksa konsumsi pakan harian di Buku Kandang dan pastikan air minum selalu segar dan mencukupi.";
        }

        return "{$statusInfo}\n\nSemua kalkulasi ransum dihitung otomatis oleh sistem formulasi RansumKu. Anda dapat melihat hasil rincian komposisi, biaya pakan, dan kecukupan gizi pada menu Ransum. Untuk pemeriksaan kesehatan ayam secara menyeluruh, disarankan konsultasi langsung dengan dokter hewan atau dinas peternakan setempat.";
    }

    /**
     * Fallback penjelasan ransum jika AI tidak aktif.
     */
    private function fallbackJelaskan(Ration $r): string
    {
        if (! $r->layak()) {
            return 'Ransum belum dapat disusun dengan bahan dan stok saat ini. Periksa saran di halaman ransum, tambah bahan atau stok yang cukup, lalu lakukan hitung ulang.';
        }

        $urut = $r->items->sortByDesc('persen');
        $utama = $urut->first();
        $ok = collect($r->hasil['gizi'] ?? [])->every(fn ($g) => $g['ok']);

        return "Sistem RansumKu memilih racikan pakan dengan biaya paling hemat yang tetap memenuhi standar nutrisi target. {$utama->nama} menjadi porsi terbesar ({$utama->persen}%) karena rasio harga terhadap kandungan nutrisinya paling optimal. "
            .($ok ? 'Seluruh target nutrisi ayam terpenuhi.' : 'Terdapat target nutrisi yang belum sepenuhnya terpenuhi, silakan cek tabel Cek Gizi.')
            .' (Penjelasan otomatis berbasis data formulasi).';
    }
}
