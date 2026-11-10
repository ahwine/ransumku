<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Services\AnomalyDetector;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LogController extends Controller
{
    public function index(Request $r): View|RedirectResponse
    {
        if ($r->query('tab') === 'dashboard') {
            return redirect()->route('laporan.index', $r->only('rentang'));
        }

        $farm = $this->farm();
        $harga = $farm->hargaPakanPerKg();

        $selectedDate = $r->query('tanggal', today()->toDateString());
        $form = $farm->logs()->whereDate('tanggal', $selectedDate)->first() ?? new DailyLog(['tanggal' => Carbon::parse($selectedDate)]);
        $isEdit = $farm->logs()->whereDate('tanggal', $selectedDate)->exists();

        $filterPeriode = $r->query('periode', 'semua');
        $cariTanggal = $r->query('cari_tanggal');

        $riwayatQuery = $farm->logs()->orderByDesc('tanggal');

        if ($cariTanggal) {
            $riwayatQuery->whereDate('tanggal', $cariTanggal);
        } elseif ($filterPeriode === 'bulan_ini') {
            $riwayatQuery->whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
        } elseif ($filterPeriode === 'bulan_lalu') {
            $riwayatQuery->whereBetween('tanggal', [now()->subMonth()->startOfMonth()->toDateString(), now()->subMonth()->endOfMonth()->toDateString()]);
        } elseif ($filterPeriode === '30_hari') {
            $riwayatQuery->where('tanggal', '>=', now()->subDays(29)->toDateString());
        } elseif ($filterPeriode === '7_hari') {
            $riwayatQuery->where('tanggal', '>=', now()->subDays(6)->toDateString());
        }

        $riwayat = $riwayatQuery->paginate(5)->withQueryString();
        $allLogs = $farm->logs()->orderByDesc('tanggal')->get();

        $yesterdayDate = today()->subDay()->toDateString();
        $yesterdayLog = $farm->logs()->whereDate('tanggal', $yesterdayDate)->first();
        $jumlahAyam = max(1, (int) $farm->jumlah_ayam);

        // Peta ringkas catatan untuk deteksi ganti tanggal di form
        $existingLogs = $farm->logs()->latest('tanggal')->limit(30)->get()->mapWithKeys(function ($l) {
            return [$l->tanggal->toDateString() => [
                'pakan_kg' => (float) $l->pakan_kg,
                'telur_butir' => (int) $l->telur_butir,
                'telur_kg' => $l->telur_kg !== null ? (float) $l->telur_kg : null,
                'ayam_mati' => (int) $l->ayam_mati,
                'biaya_lain' => (int) $l->biaya_lain,
                'catatan' => (string) ($l->catatan ?? ''),
            ]];
        });

        $beli = $farm->purchases()->with('ingredient')->latest('tanggal')->latest('id')->limit(10)->get();
        $activeIngIds = $farm->farmIngredients()->where('aktif', true)->pluck('ingredient_id')->toArray();
        $bahan = $farm->ingredients()->get()->sortBy(function ($ing) use ($activeIngIds) {
            return (in_array($ing->id, $activeIngIds, true) ? '0_' : '1_') . $ing->nama;
        })->values();
        $selisihTotal = $beli->sum(fn ($p) => ($p->harga_nyata - $p->harga_estimasi) * $p->kg);

        return view('catatan.index', compact('farm', 'form', 'isEdit', 'riwayat', 'allLogs', 'yesterdayLog', 'existingLogs', 'beli', 'bahan', 'selisihTotal', 'harga', 'jumlahAyam', 'filterPeriode', 'cariTanggal'));
    }

    public function store(Request $r, AnomalyDetector $detector): RedirectResponse
    {
        $data = $r->validate([
            'tanggal' => 'required|date|before_or_equal:today',
            'pakan_kg' => 'required|numeric|min:0|max:100000',
            'telur_butir' => 'required|integer|min:0|max:1000000',
            'telur_kg' => 'nullable|numeric|min:0|max:100000',
            'ayam_mati' => 'nullable|integer|min:0|max:100000',
            'biaya_lain' => 'nullable|integer|min:0',
            'catatan' => 'nullable|string|max:1000',
        ]);
        $data['ayam_mati'] = $data['ayam_mati'] ?? 0;
        $data['biaya_lain'] = $data['biaya_lain'] ?? 0;

        $log = $this->farm()->logs()->whereDate('tanggal', $data['tanggal'])->first();
        if ($log) {
            $log->update($data);
        } else {
            $this->farm()->logs()->create($data);
        }

        $detector->evaluate($this->farm());

        return redirect()->route('catatan.index', ['tab' => 'form'])->with('ok', 'Catatan harian disimpan.');
    }

    public function destroy(DailyLog $log): RedirectResponse
    {
        $this->own($log)->delete();

        return redirect()->route('catatan.index')->with('ok', 'Catatan dihapus.');
    }

    public function simpanPembelian(Request $r): RedirectResponse
    {
        $data = $r->validate([
            'ingredient_id' => 'required|integer',
            'kg' => 'required|numeric|min:0.1|max:100000',
            'harga_nyata' => 'required|integer|min:0',
            'tanggal' => 'required|date|before_or_equal:today',
            'tambah_stok' => 'nullable|boolean',
        ]);

        $farm = $this->farm();
        $ing = $farm->ingredients()->findOrFail($data['ingredient_id']);
        $est = (int) ($farm->farmIngredients()->where('ingredient_id', $ing->id)->value('harga') ?? $ing->harga_default);

        $farm->purchases()->create([
            'ingredient_id' => $data['ingredient_id'],
            'kg' => $data['kg'],
            'harga_nyata' => $data['harga_nyata'],
            'harga_estimasi' => $est,
            'tanggal' => $data['tanggal'],
        ]);

        $msg = 'Pembelian pakan dicatat.';
        if ($r->boolean('tambah_stok')) {
            $farmIng = $farm->farmIngredients()->where('ingredient_id', $ing->id)->first();
            if ($farmIng) {
                $farmIng->increment('stok_kg', $data['kg']);
                $farmIng->update([
                    'aktif' => true,
                    'harga' => $data['harga_nyata'],
                    'harga_updated_at' => now(),
                ]);
                $msg = 'Pembelian dicatat & stok diperbarui.';
            }
        }

        return redirect()->route('catatan.index', ['tab' => 'beli'])->with('ok', $msg);
    }
}
