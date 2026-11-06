<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\AiAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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

        $alerts = $farm->alerts()->latest('tanggal_data')->latest('id')->limit(30)->get();
        $aiAktif = app(AiAssistant::class)->tersedia();

        // Ambil semua sesi percakapan peternak (pinned di atas, lalu terbaru)
        $chatSessions = $farm->chatSessions()
            ->withCount('messages')
            ->with(['messages' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderByDesc('pinned')
            ->orderByDesc('updated_at')
            ->get();

        $sessionParam = $r->query('session');
        $activeSession = null;
        $chats = collect();
        $isGenerating = false;

        if ($sessionParam === 'baru') {
            session()->forget('active_chat_session_id');
            $activeSession = null;
            $chats = collect();
        } elseif (is_numeric($sessionParam) && (int) $sessionParam > 0) {
            $activeSession = $chatSessions->firstWhere('id', (int) $sessionParam);
            if ($activeSession) {
                session(['active_chat_session_id' => $activeSession->id]);
                $chats = $activeSession->messages()->orderBy('id')->get();
            }
        } else {
            // Ketika peternak kembali ke asisten (tanpa parameter ?session=):
            // 1. Cek sesi yang disimpan di PHP session
            $storedId = session('active_chat_session_id');
            if ($storedId && ($s = $chatSessions->firstWhere('id', (int) $storedId))) {
                $activeSession = $s;
            } elseif ($chatSessions->isNotEmpty()) {
                // 2. Cek apakah ada sesi yang sedang diproses AI di latar belakang
                $generatingSession = $chatSessions->first(fn ($s) => Cache::has("chat_generating_{$s->id}"));
                if ($generatingSession) {
                    $activeSession = $generatingSession;
                } else {
                    // 3. Fallback ke sesi percakapan terakhir yang memiliki pesan
                    $activeSession = $chatSessions->firstWhere('messages_count', '>', 0) ?? $chatSessions->first();
                }
            }

            if ($activeSession) {
                session(['active_chat_session_id' => $activeSession->id]);
                $chats = $activeSession->messages()->orderBy('id')->get();
            }
        }

        if ($activeSession) {
            $lastMsg = $chats->last();
            $isGenerating = Cache::has("chat_generating_{$activeSession->id}") || ($lastMsg && $lastMsg->role === 'user');
        }

        return view('asisten.index', compact('alerts', 'chatSessions', 'activeSession', 'chats', 'aiAktif', 'isGenerating'));
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
            session()->forget('active_chat_session_id');

            if ($r->wantsJson() || $r->ajax()) {
                return response()->json(['status' => 'success', 'message' => 'Semua riwayat obrolan berhasil dibersihkan.']);
            }

            return redirect()->route('asisten.index', ['tab' => 'tanya', 'session' => 'baru'])
                ->with('pesan', 'Seluruh riwayat obrolan berhasil dibersihkan.');
        }

        // 2. Aksi Hapus Satu Sesi Tertentu
        if ($aksi === 'hapus_session') {
            $sessionId = (int) $r->input('session_id');
            $session = $farm->chatSessions()->find($sessionId);
            if ($session) {
                $session->delete();
            }
            if ((int) session('active_chat_session_id') === $sessionId) {
                session()->forget('active_chat_session_id');
            }
            Cache::forget("chat_generating_{$sessionId}");

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
            session(['active_chat_session_id' => $session->id]);

            $messages = $session->messages()->orderBy('id')->get()->map(fn ($m) => [
                'role' => $m->role,
                'isi' => $m->isi,
                'time' => $m->created_at->format('H:i'),
            ]);

            $lastMsg = $session->messages()->latest('id')->first();
            $isGenerating = Cache::has("chat_generating_{$session->id}") || ($lastMsg && $lastMsg->role === 'user');

            return response()->json([
                'status' => 'success',
                'session_id' => $session->id,
                'session_title' => $session->judul,
                'is_generating' => $isGenerating,
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

        session(['active_chat_session_id' => $session->id]);

        // Simpan pesan user
        $userMsg = $session->messages()->create([
            'farm_id' => $farm->id,
            'role' => 'user',
            'isi' => $pesan,
        ]);

        $session->touch();

        // Tandai proses generating di latar belakang
        Cache::put("chat_generating_{$session->id}", true, 180);

        // Jalankan pembuatan jawaban AI di background CLI
        $this->dispatchBackgroundGeneration($session->id);

        // Respon langsung ke client (tidak menunggu Gemini selesai)
        if ($r->wantsJson() || $r->ajax()) {
            return response()->json([
                'status' => 'success',
                'session_id' => $session->id,
                'session_title' => $session->judul,
                'is_new_session' => $isNewSession,
                'is_generating' => true,
                'user_message' => $pesan,
                'created_at' => $userMsg->created_at->format('H:i'),
            ]);
        }

        return redirect()->route('asisten.index', [
            'tab' => 'tanya',
            'session' => $session->id,
        ])->withFragment('chat-bawah');
    }

    public function status(Request $r)
    {
        $farm = $this->farm();
        $sessionId = (int) $r->query('session_id');
        $session = $farm->chatSessions()->find($sessionId);

        if (! $session) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $isGenerating = Cache::has("chat_generating_{$sessionId}");
        $lastMessage = $session->messages()->latest('id')->first();

        // Jika pesan terakhir adalah assistant dan cache generating sudah selesai
        if ($lastMessage && $lastMessage->role === 'assistant' && ! $isGenerating) {
            return response()->json([
                'status' => 'completed',
                'session_id' => $session->id,
                'message_id' => $lastMessage->id,
                'jawaban' => $lastMessage->isi,
                'created_at' => $lastMessage->created_at->format('H:i'),
            ]);
        }

        if ($isGenerating || ($lastMessage && $lastMessage->role === 'user')) {
            return response()->json([
                'status' => 'generating',
                'session_id' => $session->id,
            ]);
        }

        return response()->json([
            'status' => 'idle',
            'session_id' => $session->id,
        ]);
    }

    protected function dispatchBackgroundGeneration(int $sessionId): void
    {
        if (app()->runningUnitTests()) {
            Artisan::call('asisten:generate', ['session_id' => $sessionId]);

            return;
        }

        $artisanPath = base_path('artisan');
        $phpBinary = PHP_BINARY ?: '';

        if ($phpBinary === '' && is_file(base_path('frankenphp'))) {
            $phpBinary = base_path('frankenphp');
        } elseif ($phpBinary === '') {
            $phpBinary = 'php';
        }

        if (str_ends_with(strtolower($phpBinary), 'php-cgi.exe')) {
            $phpBinary = str_ireplace('php-cgi.exe', 'php.exe', $phpBinary);
        }

        $isFrankenPhp = str_contains(strtolower(basename($phpBinary)), 'frankenphp');
        $cliRunner = $isFrankenPhp
            ? escapeshellarg($phpBinary).' php-cli'
            : escapeshellarg($phpBinary);

        if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
            $cmd = sprintf(
                'start /B "" %s %s asisten:generate %d > NUL 2>&1',
                $cliRunner,
                escapeshellarg($artisanPath),
                $sessionId
            );
            pclose(popen($cmd, 'r'));
        } else {
            $cmd = sprintf(
                '%s %s asisten:generate %d > /dev/null 2>&1 &',
                $cliRunner,
                escapeshellarg($artisanPath),
                $sessionId
            );
            @exec($cmd);
        }
    }

    public function baru(Request $r)
    {
        session()->forget('active_chat_session_id');

        if ($r->wantsJson() || $r->ajax()) {
            return response()->json(['status' => 'success']);
        }

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

        $sessionId = $session->id;
        $session->delete();

        if ((int) session('active_chat_session_id') === $sessionId) {
            session()->forget('active_chat_session_id');
        }
        Cache::forget("chat_generating_{$sessionId}");

        return redirect()->route('asisten.index', ['tab' => 'tanya'])
            ->with('pesan', 'Sesi percakapan berhasil dihapus.');
    }

    public function hapusChat()
    {
        $farm = $this->farm();
        $farm->chats()->delete();
        $farm->chatSessions()->delete();
        session()->forget('active_chat_session_id');

        return redirect()->route('asisten.index', ['tab' => 'tanya', 'session' => 'baru'])
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
