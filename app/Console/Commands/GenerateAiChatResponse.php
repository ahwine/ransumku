<?php

namespace App\Console\Commands;

use App\Models\ChatSession;
use App\Services\AiAssistant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateAiChatResponse extends Command
{
    /**
     * Nama dan tanda tangan perintah console.
     */
    protected $signature = 'asisten:generate {session_id : ID sesi percakapan}';

    /**
     * Deskripsi perintah console.
     */
    protected $description = 'Menjalankan proses pembuatan jawaban AI asisten di latar belakang (background CLI)';

    /**
     * Eksekusi perintah console.
     */
    public function handle(AiAssistant $ai): int
    {
        @ignore_user_abort(true);
        @set_time_limit(180);

        $sessionId = (int) $this->argument('session_id');
        $session = ChatSession::with('farm')->find($sessionId);

        if (! $session || ! $session->farm) {
            $this->error("Sesi chat #{$sessionId} tidak ditemukan.");

            return self::FAILURE;
        }

        $farm = $session->farm;

        // Ambil pesan terakhir pada sesi ini
        $latestMessage = $session->messages()->latest('id')->first();

        // Jika tidak ada pesan atau pesan terakhir bukan dari user, tidak perlu generate
        if (! $latestMessage || $latestMessage->role !== 'user') {
            $this->info("Tidak ada pertanyaan user yang menunggu jawaban pada sesi #{$sessionId}.");
            Cache::forget("chat_generating_{$sessionId}");

            return self::SUCCESS;
        }

        // Kunci atomic lock agar tidak dieksekusi ganda jika dipanggil bersamaan
        $lock = Cache::lock("lock_chat_generate_{$sessionId}", 120);
        if (! $lock->get()) {
            $this->warn("Sesi #{$sessionId} sudah sedang diproses oleh worker lain.");

            return self::SUCCESS;
        }

        try {
            Cache::put("chat_generating_{$sessionId}", true, 180);

            // Bangun riwayat sebelum pesan terakhir untuk konteks Gemini
            $riwayat = $session->messages()
                ->where('id', '<', $latestMessage->id)
                ->latest('id')
                ->limit(8)
                ->get()
                ->reverse()
                ->values();

            $jawaban = $ai->jawab($farm, $latestMessage->isi, $riwayat);

            if (blank($jawaban)) {
                $jawaban = 'Mohon maaf, saya belum dapat menyusun jawaban saat ini. Silakan ulangi beberapa saat lagi.';
            }

            // Simpan jawaban asisten
            $session->messages()->create([
                'farm_id' => $farm->id,
                'role' => 'assistant',
                'isi' => $jawaban,
            ]);

            $session->touch();
            $this->info("Berhasil generate jawaban untuk sesi #{$sessionId}.");
        } catch (\Throwable $e) {
            Log::error("Gagal generate respons asisten untuk sesi #{$sessionId}: ".$e->getMessage(), [
                'exception' => $e,
            ]);

            // Buat pesan fallback agar user tidak stuck loading selamanya
            $session->messages()->create([
                'farm_id' => $farm->id,
                'role' => 'assistant',
                'isi' => 'Mohon maaf, terjadi kendala saat memproses jawaban Anda. Silakan coba tanyakan kembali.',
            ]);
            $session->touch();
        } finally {
            Cache::forget("chat_generating_{$sessionId}");
            $lock->release();
        }

        return self::SUCCESS;
    }
}

