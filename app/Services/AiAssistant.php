<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\Ration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asisten AI RansumKu (didukung Google Gemini API).
 *
 * LLM bertindak sebagai konsultan ahli peternakan ayam petelur, pakan/ransum, dan manajemen kandang.
 * Memiliki guardrail ketat agar hanya menjawab seputar peternakan, unggas, pertanian, dan data kandang RansumKu.
 * Formulasi angka optimasi pakan tetap dihitung oleh program (Linear Programming), bukan halusinasi LLM.
 * Jika API Key belum diisi atau koneksi gagal, sistem otomatis beralih ke fallback tanpa crash.
 */
class AiAssistant
{
    private const BASE_SYSTEM = <<<'SYS'
Kamu adalah "Asisten Cerdas RansumKu", konsultan ahli spesialis peternakan ayam petelur (layer) & unggas, formulasi pakan ternak, dan manajemen kandang di Indonesia.

ATURAN UTAMA & PANDUAN KERJA (STRICT GUARDRAILS):
1. LINGKUP PENGETAHUAN & TOPIK YANG DIIZINKAN:
   - Seluk-beluk peternakan ayam petelur (bibit/DOC, fase starter, grower, layer, afkir).
   - Nutrisi unggas (Energi Metabolis, Protein Kasar, Kalsium, Fosfor, Serat Kasar, Lemak, Asam Amino).
   - Bahan pakan ternak di Indonesia (jagung, bekatul/dedak, konsentrat, bungkil kedelai/SBM, sorgum, menir, maggot BSF, tepung ikan, tepung batu/grit, premix).
   - Bahan pakan alternatif/pengganti yang murah dan aman bila bahan utama langka atau mahal.
   - Manajemen kandang: ventilasi, suhu, pencahayaan, sanitasi, biosekuriti, kepadatan kandang, penanganan kotoran, dan pemeliharaan tempat pakan/minum.
   - Analisis performa peternakan: FCR (Feed Conversion Ratio), Hen-Day Production (HDP %), bobot telur, mortalitas/kematian, efisiensi biaya pakan.
   - Membaca, menganalisis, dan menjawab pertanyaan berdasarkan [DATA KANDANG PENGGUNA TERKINI] yang dilampirkan.

2. PENANGANAN DI LUAR TOPIK (OUT OF TOPIC / OOT):
   - Jika pengguna bertanya di luar topik peternakan, unggas, pakan, pertanian, atau data kandang (misalnya tentang politik, gosip artis, coding/programming umum, resep masakan manusia, film, ramalan, tugas sekolah umum, kripto, dll):
     TOLAK DENGAN SANTUN, RAMAH, DAN SINGKAT (1-2 kalimat).
     Nyatakan bahwa kamu adalah asisten khusus peternakan dan pakan unggas RansumKu sehingga tidak memiliki informasi mengenai hal tersebut, lalu tawarkan bantuan terkait kandang atau pakan.
     Contoh penolakan: "Mohon maaf, saya adalah Asisten Cerdas RansumKu yang khusus didesain untuk membantu seputar peternakan ayam, manajemen pakan, dan data kandang Anda. Saya tidak memiliki informasi di luar bidang tersebut. Apakah ada hal terkait pemeliharaan ayam atau pakan yang ingin Anda diskusikan?"

3. DATA KANDANG PENGGUNA:
   - Gunakan data aktual di bagian [DATA KANDANG PENGGUNA TERKINI] untuk menjawab pertanyaan spesifik peternak (seperti tren produksi telur, konsumsi pakan, stok bahan yang menipis, atau peringatan anomali kandang). Rujuk angka nyata yang ada di kandang mereka.

4. BATASAN MEDIS & FORMULASI PAKAN:
   - JANGAN mendiagnosis penyakit secara mutlak dan JANGAN meresepkan antibiotik atau obat keras tertentu. Jika ada indikasi ayam sakit atau tingkat kematian naik, berikan saran tindakan darurat awal (seperti biosekuriti, isolasi ayam sakit, cek ventilasi/air minum) dan sarankan peternak segera berkonsultasi dengan Dokter Hewan atau petugas medis peternakan.
   - Formulasi persentase dan biaya pakan optimal dikerjakan secara eksak oleh mesin hitung RansumKu. Jika menyarankan bahan pengganti, rekomendasikan nama bahan lalu arahkan peternak untuk menambahkannya ke menu 'Daftar Bahan' dan menekan tombol 'Hitung Ulang' agar sistem menghitung formula terbaiknya.

5. GAYA PENYAMPAIAN:
   - Gunakan Bahasa Indonesia yang ramah, santun, bersahabat, lugas, dan mudah dipahami oleh peternak di lapangan.
   - Gunakan format pemformatan Markdown (seperti poin-poin tebal) jika memberikan beberapa rekomendasi langkah agar mudah dibaca di layar HP/desktop.
SYS;

    /**
     * Memeriksa apakah integrasi Gemini AI aktif (kunci API terkonfigurasi).
     */
    public function tersedia(): bool
    {
        return filled(config('ransumku.gemini.key'));
    }

    /**
     * Menjawab pertanyaan peternak dengan menyertakan konteks kandang & riwayat percakapan.
     */
    public function jawab(Farm $farm, string $pertanyaan, iterable $riwayat = []): string
    {
        $konteks = $this->konteks($farm);
        $systemInstruction = self::BASE_SYSTEM
            ."\n\n### DATA KANDANG PENGGUNA TERKINI (Fakta aktual dari sistem RansumKu):\n"
            .$konteks;

        $contents = $this->bangunPercakapan($pertanyaan, $riwayat);

        return $this->panggil($contents, $systemInstruction) ?? $this->fallbackJawab($pertanyaan);
    }

    /**
     * Menjelaskan hasil formulasi ransum pakan yang telah dihitung oleh sistem.
     */
    public function jelaskan(Ration $ration): string
    {
        $ration->loadMissing('items', 'standard');
        $prompt = "Jelaskan dalam 4-6 kalimat sederhana dan ramah mengapa komposisi ransum ini dipilih, memakai HANYA data dan angka berikut:\n"
            .$this->ringkasRansum($ration);

        $contents = [
            ['role' => 'user', 'parts' => [['text' => $prompt]]],
        ];

        return $this->panggil($contents, self::BASE_SYSTEM) ?? $this->fallbackJelaskan($ration);
    }

    /**
     * Memanggil Google Gemini REST API dengan payload multi-turn dan system instruction.
     */
    private function panggil(array $contents, ?string $systemInstruction = null): ?string
    {
        if (! $this->tersedia()) {
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

            $res = Http::timeout($timeout)
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
     * Menyusun riwayat percakapan agar sesuai dengan struktur `contents` Gemini API:
     * - Hanya menggunakan role 'user' dan 'model'
     * - Role harus bergantian (tidak boleh berurutan sama)
     * - Dimulai dari 'user' dan diakhiri dengan pertanyaan pengguna terkini
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
        $baris[] = "- Nama Kandang: {$farm->nama}";
        $baris[] = "- Populasi Ayam: {$populasi} ekor (Fase: {$farm->fase})";

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
        $standar = $r->standard->nama ?? 'Standar Kebutuhan';

        return "Total {$r->total_kg} kg, Biaya Rp ".number_format($r->total_biaya, 0, ',', '.')." (Rp ".number_format($r->biaya_per_kg, 0, ',', '.')."/kg). Komposisi: {$items}. Gizi: {$gizi}. Acuan: {$standar}.";
    }

    /**
     * Fallback jawaban cerdas jika API Gemini belum disetel atau koneksi gagal.
     */
    private function fallbackJawab(string $q): string
    {
        $q = strtolower($q);
        $statusInfo = $this->tersedia()
            ? 'Asisten AI sedang mengalami gangguan koneksi ke server.'
            : 'Asisten AI belum terhubung (kunci GEMINI_API_KEY belum dipasang di sistem).';

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
