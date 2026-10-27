<?php

namespace App\Http\Controllers;

use App\Services\FarmBootstrap;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function beranda()
    {
        $farm = $this->farm();

        $logs = $farm->logs()->orderByDesc('tanggal')->limit(7)->get()->reverse()->values();
        $hariIni = $farm->logs()->orderByDesc('tanggal')->first();
        $ration = $farm->lastRation();

        // Biaya pakan per kg telur (estimasi) pada catatan terakhir
        $biayaKgTelur = null;
        if ($hariIni && $ration && $hariIni->telurKgEfektif() > 0) {
            $biayaKgTelur = $hariIni->pakan_kg * $ration->biaya_per_kg / $hariIni->telurKgEfektif();
        }

        $alerts = $farm->alerts()->where('tanggal_data', '>=', now()->subDays(5))->latest('tanggal_data')->latest('id')->get();
        $terlama = $farm->hargaTerlamaDiperbarui();
        $hargaUsangHari = $terlama && $terlama->lt(now()->subDays(config('ransumku.harga_usang_hari')))
            ? (int) $terlama->diffInDays(now()) : null;

        return view('beranda', compact('farm', 'logs', 'hariIni', 'ration', 'biayaKgTelur', 'alerts', 'hargaUsangHari'));
    }

    public function lainnya()
    {
        return view('lainnya', ['farm' => $this->farm()]);
    }

    public function tentang()
    {
        return view('tentang');
    }

    /** Setel ulang data kandang ini ke data demo (hanya data milik user sendiri). */
    public function isiDemo(Request $r, FarmBootstrap $boot)
    {
        $boot->resetDemo($this->farm());

        return redirect()->route('beranda')->with('ok', 'Data demo sudah diisi ulang. Peringatan dini akan muncul di beranda.');
    }
}
