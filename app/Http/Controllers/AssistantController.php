<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\AiAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssistantController extends Controller
{
    public function index(Request $r)
    {
        $farm = $this->farm();

        // Jika peternak membuka tab peringatan, otomatis tandai semua peringatan sudah dibaca (hilangkan titik merah)
        if ($r->query('tab') === 'peringatan') {
            $farm->alerts()->where('dibaca', false)->update(['dibaca' => true]);
        }

        $alerts = $farm->alerts()->latest('tanggal_data')->latest('id')->limit(10)->get();
        $aiAktif = app(AiAssistant::class)->tersedia();

        // Ambil semua sesi percakapan peternak (pinned di atas, lalu terbaru)
        $chatSessions = $farm->chatSessions()
            ->withCount('messages')
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->get();

        $sessionParam = $r->query('session');
        $activeSession = null;
        $chats = collect();

        // Default selalu buka sesi obrolan baru setiap kali dimuat / di-refresh
        if (is_numeric($sessionParam) && (int) $sessionParam > 0) {
            $activeSession = $chatSessions->firstWhere('id', (int) $sessionParam);
            $chats = $activeSession ? $activeSession->messages()->orderBy('id')->get() : collect();
        }

        return view('asisten.index', compact('alerts', 'chatSessions', 'activeSession', 'chats', 'aiAktif'));
    }

    public function chat(Request $r, AiAssistant $ai)
    {
        @set_time_limit(120);
        $farm = $this->farm();
        $aksi = $r->input('aksi');

        // 1. Aksi Hapus Seluruh Riwayat Obrolan
        if ($aksi === 'hapus_semua') {
            $farm->chats()->delete();
            $farm->chatSessions()->delete();

            if ($r->wantsJson() || $r->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Semua riwayat obrolan berhasil dibersihkan.']);
            }

            return redirect()->route('asisten.index', ['tab' => 'tanya'])
                ->with('pesan', 'Seluruh riwayat obrolan berhasil dibersihkan.');
        }

        // 2. Aksi Hapus Satu Sesi Tertentu
        if ($aksi === 'hapus_session') {
            $sessionId = (int) $r->input('session_id');
            $session = $farm->chatSessions()->find($sessionId);
            if ($session) {
                $session->delete();
            }

            if ($r->wantsJson() || $r->ajax()) {
                return response()->json(['status' => 'success', 'deleted_id' => $sessionId]);
            }

            return redirect()->route('asisten.index', ['tab' => 'tanya'])
                ->with('pesan', 'Sesi percakapan berhasil dihapus.');
        }

        // 3. Aksi Pin / Unpin Sesi
        if ($aksi === 'pin_session') {
            $sessionId = (int) $r->input('session_id');
            $session = $farm->chatSessions()->find($sessionId);
            if ($session) {
                $session->update(['pinned' => ! $session->pinned]);
            }

            if ($r->wantsJson() || $r->ajax()) {
                return response()->json(['status' => 'success', 'pinned' => (bool) ($session?->pinned)]);
            }

            return back()->with('pesan', ($session && $session->pinned) ? 'Percakapan disematkan.' : 'Penyematan dilepas.');
        }

        // 4. Aksi Ambil Pesan Satu Sesi (AJAX switch tanpa reload)
        if ($aksi === 'ambil_session') {
            $sessionId = (int) $r->input('session_id');
            $session = $farm->chatSessions()->find($sessionId);
            if (! $session) {
                return response()->json(['status' => 'error', 'message' => 'Sesi tidak ditemukan.'], 404);
            }
            $messages = $session->messages()->orderBy('id')->get()->map(fn ($m) => [
                'role' => $m->role,
                'isi' => $m->isi,
                'time' => $m->created_at->format('H:i'),
            ]);

            return response()->json([
                'status' => 'success',
                'session_id' => $session->id,
                'session_title' => $session->judul,
                'messages' => $messages,
            ]);
        }

        // 5. Kirim Pesan Chat Baru
        $pesan = trim((string) $r->input('pesan', ''));
        if ($pesan === '') {
            if ($r->wantsJson() || $r->ajax()) {
                return response()->json(['status' => 'error', 'message' => 'Pesan tidak boleh kosong.'], 422);
            }

            return back()->with('pesan', 'Pesan tidak boleh kosong.');
        }

        $sessionId = $r->input('session_id') ? (int) $r->input('session_id') : null;
        $isNewSession = false;
        $session = null;

        if ($sessionId) {
            $session = $farm->chatSessions()->find($sessionId);
        }

        if (! $session) {
            $judul = Str::limit($pesan, 32, '...');
            $session = $farm->chatSessions()->create([
                'judul' => $judul ?: 'Konsultasi Baru',
                'pinned' => false,
            ]);
            $isNewSession = true;
        }

        // Ambil riwayat chat dalam sesi ini untuk konteks multi-turn Gemini
        $riwayat = $session->messages()->latest('id')->limit(8)->get()->reverse()->values();

        // Simpan pesan user
        $session->messages()->create([
            'farm_id' => $farm->id,
            'role' => 'user',
            'isi' => $pesan,
        ]);

        // Dapatkan jawaban dari AI Gemini
        $jawaban = $ai->jawab($farm, $pesan, $riwayat);

        // Simpan jawaban asisten AI
        $assistantMsg = $session->messages()->create([
            'farm_id' => $farm->id,
            'role' => 'assistant',
            'isi' => $jawaban,
        ]);

        // Perbarui waktu pembaruan sesi agar naik ke atas daftar Recents
        $session->touch();

        // Respon JSON untuk mode Real-Time AJAX (tanpa refresh halaman ala ChatGPT)
        if ($r->wantsJson() || $r->ajax()) {
            return response()->json([
                'status' => 'success',
                'session_id' => $session->id,
                'session_title' => $session->judul,
                'is_new_session' => $isNewSession,
                'jawaban' => $jawaban,
                'user_message' => $pesan,
                'created_at' => $assistantMsg->created_at->format('H:i'),
            ]);
        }

        return redirect()->route('asisten.index', [
            'tab' => 'tanya',
            'session' => $session->id,
        ])->withFragment('chat-bawah');
    }

    public function baru()
    {
        return redirect()->route('asisten.index', ['tab' => 'tanya', 'session' => 'baru']);
    }

    public function pinSession(ChatSession $session)
    {
        abort_unless($session->farm_id === $this->farm()->id, 403);

        $session->update(['pinned' => ! $session->pinned]);

        return back()->with('pesan', $session->pinned ? 'Percakapan disematkan.' : 'Penyematan dilepas.');
    }

    public function hapusSession(ChatSession $session)
    {
        abort_unless($session->farm_id === $this->farm()->id, 403);

        $session->delete();

        return redirect()->route('asisten.index', ['tab' => 'tanya'])
            ->with('pesan', 'Sesi percakapan berhasil dihapus.');
    }

    public function hapusChat()
    {
        $farm = $this->farm();
        $farm->chats()->delete();
        $farm->chatSessions()->delete();

        return redirect()->route('asisten.index', ['tab' => 'tanya'])
            ->with('pesan', 'Seluruh riwayat obrolan berhasil dibersihkan.');
    }

    public function bacaPeringatan(Request $r)
    {
        $farm = $this->farm();
        if ($r->filled('alert_id')) {
            $farm->alerts()->where('id', $r->input('alert_id'))->update(['dibaca' => true]);
        } else {
            $farm->alerts()->update(['dibaca' => true]);
        }

        if ($r->wantsJson() || $r->ajax()) {
            $unreadCount = $farm->alerts()->where('dibaca', false)->count();
            return response()->json(['success' => true, 'unread_count' => $unreadCount]);
        }

        return back();
    }
}
