<?php

namespace App\Http\Controllers;

use App\Services\AiAssistant;
use Illuminate\Http\Request;

class AssistantController extends Controller
{
    public function index(Request $r)
    {
        $farm = $this->farm();
        $alerts = $farm->alerts()->latest('tanggal_data')->latest('id')->limit(10)->get();
        $chats = $farm->chats()->orderBy('id')->limit(40)->get();
        $aiAktif = app(AiAssistant::class)->tersedia();

        return view('asisten.index', compact('alerts', 'chats', 'aiAktif'));
    }

    public function chat(Request $r, AiAssistant $ai)
    {
        $data = $r->validate(['pesan' => 'required|string|max:500']);
        $farm = $this->farm();

        $farm->chats()->create(['role' => 'user', 'isi' => $data['pesan']]);
        $farm->chats()->create(['role' => 'assistant', 'isi' => $ai->jawab($farm, $data['pesan'])]);

        return redirect()->route('asisten.index', ['tab' => 'tanya'])->withFragment('chat-bawah');
    }

    public function bacaPeringatan()
    {
        $this->farm()->alerts()->update(['dibaca' => true]);

        return back();
    }
}
