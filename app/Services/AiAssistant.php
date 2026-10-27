<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\Ration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asisten AI (Gemini). LLM HANYA menjelaskan & menjawab; semua angka berasal dari program.
 * Jika kunci kosong/gagal, kembalikan jawaban fallback sederhana (tidak crash).
 */
class AiAssistant
{
    private const SYSTEM = 'Kamu asisten peternak ayam petelur. Jawab singkat, bahasa Indonesia sederhana. '
        .'JANGAN menghitung komposisi ransum sendiri; angka berasal dari program. Jangan mendiagnosis penyakit. '
        .'Jika saran bahan pengganti, sebutkan bahan umum dan sarankan peternak memasukkannya ke daftar bahan lalu menekan Hitung Ulang.';

    public function tersedia(): bool
    {
        return filled(config('ransumku.gemini.key'));
    }

    public function jawab(Farm $farm, string $pertanyaan): string
    {
        $konteks = $this->konteks($farm);
        $prompt = "Konteks kandang (dari program):\n{$konteks}\n\nPertanyaan peternak: {$pertanyaan}";

        return $this->panggil($prompt) ?? $this->fallbackJawab($pertanyaan);
    }

    public function jelaskan(Ration $ration): string
    {
        $ration->loadMissing('items', 'standard');
        $prompt = "Jelaskan dalam 4-6 kalimat sederhana mengapa komposisi ransum ini dipilih, memakai HANYA angka berikut:\n".$this->ringkasRansum($ration);

        return $this->panggil($prompt) ?? $this->fallbackJelaskan($ration);
    }

    private function panggil(string $prompt): ?string
    {
        if (! $this->tersedia()) {
            return null;
        }

        try {
            $cfg = config('ransumku.gemini');
            $res = Http::timeout($cfg['timeout'])
                ->withHeaders(['x-goog-api-key' => $cfg['key']])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$cfg['model']}:generateContent", [
                    'system_instruction' => ['parts' => [['text' => self::SYSTEM]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 600],
                ]);

            if (! $res->successful()) {
                Log::warning('Gemini gagal: '.$res->status());

                return null;
            }

            $teks = trim((string) data_get($res->json(), 'candidates.0.content.parts.0.text', ''));

            return $teks !== '' ? $teks : null;
        } catch (\Throwable $e) {
            Log::warning('Gemini error: '.$e->getMessage());

            return null;
        }
    }

    private function konteks(Farm $farm): string
    {
        $baris = ["Kandang: {$farm->nama}, {$farm->jumlah_ayam} ayam, fase {$farm->fase}."];

        $bahan = $farm->farmIngredients()->with('ingredient')->where('aktif', true)->get()
            ->map(fn ($fi) => "{$fi->ingredient->nama} (Rp {$fi->harga}/kg, stok {$fi->stok_kg} kg)")->implode('; ');
        $baris[] = 'Bahan aktif: '.($bahan ?: '-');

        if ($r = $farm->lastRation()) {
            $baris[] = 'Ransum terakhir: '.$this->ringkasRansum($r->load('items', 'standard'));
        }

        $logs = $farm->logs()->orderByDesc('tanggal')->limit(7)->get()->reverse();
        if ($logs->isNotEmpty()) {
            $baris[] = 'Catatan 7 hari terakhir: '.$logs->map(fn ($l) => $l->tanggal->format('d/m').": pakan {$l->pakan_kg} kg, telur {$l->telur_butir}, mati {$l->ayam_mati}")->implode(' | ');
        }

        return implode("\n", $baris);
    }

    private function ringkasRansum(Ration $r): string
    {
        $items = $r->items->map(fn ($i) => "{$i->nama} {$i->persen}% ({$i->kg} kg)")->implode(', ');
        $gizi = collect($r->hasil['gizi'] ?? [])->map(fn ($g) => "{$g['label']} {$g['nilai']}{$g['satuan']} (target {$g['target']}, ".($g['ok'] ? 'terpenuhi' : 'belum').')')->implode('; ');

        return "Total {$r->total_kg} kg, biaya estimasi Rp {$r->total_biaya} (Rp {$r->biaya_per_kg}/kg). Komposisi: {$items}. Gizi: {$gizi}. Acuan: ".($r->standard->nama ?? '-').'.';
    }

    private function fallbackJawab(string $q): string
    {
        $q = strtolower($q);
        $info = $this->tersedia() ? 'Asisten AI sedang tidak bisa dihubungi.' : 'Asisten AI belum diaktifkan (kunci API belum diisi).';

        if (str_contains($q, 'ganti') || str_contains($q, 'pengganti')) {
            return "{$info} Saran umum: bahan pengganti jagung bisa menir atau sorgum; pengganti konsentrat bisa bungkil kedelai. Masukkan bahan itu ke daftar bahan beserta harga dan stoknya, lalu tekan Hitung Ulang agar program menghitung komposisinya.";
        }

        return "{$info} Semua angka ransum dihitung oleh program; lihat halaman hasil ransum untuk komposisi, biaya, dan cek gizi. Untuk kondisi ayam, periksa langsung atau hubungi dokter hewan.";
    }

    private function fallbackJelaskan(Ration $r): string
    {
        if (! $r->layak()) {
            return 'Ransum belum bisa disusun dengan bahan dan stok saat ini. Lihat saran di atas, tambah bahan atau stok, lalu hitung ulang.';
        }
        $urut = $r->items->sortByDesc('persen');
        $utama = $urut->first();
        $ok = collect($r->hasil['gizi'] ?? [])->every(fn ($g) => $g['ok']);

        return "Program memilih campuran dengan biaya terendah yang tetap memenuhi target gizi. {$utama->nama} jadi porsi terbesar ({$utama->persen}%) karena harga per kandungan gizinya paling menguntungkan. "
            .($ok ? 'Semua target gizi terpenuhi.' : 'Ada target gizi yang belum terpenuhi, cek tabel Cek Gizi.')
            .' Biaya hanya estimasi dan bergantung pada harga yang Anda isi. (Penjelasan sederhana tanpa AI.)';
    }
}
