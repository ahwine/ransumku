<?php

namespace App\Http\Controllers;

use App\Models\DailyLog;
use App\Models\Purchase;
use App\Services\AnomalyDetector;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $r)
    {
        $farm = $this->farm();
        $rentang = $r->query('rentang') === 'bulan' ? 30 : 7;
        $harga = $farm->hargaPakanPerKg();

        $logs = $farm->logs()->where('tanggal', '>=', now()->subDays($rentang - 1)->toDateString())
            ->orderBy('tanggal')->get();

        $labels = $logs->map(fn ($l) => $l->tanggal->format('j/n'))->all();
        $laporan = $this->laporanPeriodik($farm, $harga, $rentang);

        $rataPakan = $laporan['ini']['hari'] ? round($laporan['ini']['pakan'] / $laporan['ini']['hari'], 1) : 0;
        $rataTelur = round($laporan['ini']['rata_telur']);
        $rataBiaya = $laporan['ini']['hari'] ? round($laporan['ini']['biaya_pakan'] / $laporan['ini']['hari']) : 0;
        $rataEfisiensi = round($laporan['ini']['biaya_kg_telur']);

        $grafik = [
            'pakan' => $this->chart('bar', $labels, 'Pakan (kg)', $logs->pluck('pakan_kg')->map(fn ($v) => (float) $v)->all(), '#c97a1e', $rataPakan),
            'telur' => $this->chart('line', $labels, 'Telur (butir)', $logs->pluck('telur_butir')->all(), '#116a3a', $rataTelur),
            'biaya' => $this->chart('line', $labels, 'Biaya pakan (Rp, estimasi)', $logs->map(fn ($l) => round($l->pakan_kg * $harga))->all(), '#0e7490', $rataBiaya),
            'efisiensi' => $this->chart('line', $labels, 'Biaya pakan per kg telur (Rp, estimasi)', $logs->map(fn ($l) => $l->telurKgEfektif() > 0 ? round($l->pakan_kg * $harga / $l->telurKgEfektif()) : 0)->all(), '#7c3aed', $rataEfisiensi),
        ];

        $selectedDate = $r->query('tanggal', today()->toDateString());
        $form = $farm->logs()->whereDate('tanggal', $selectedDate)->first() ?? new DailyLog(['tanggal' => \Illuminate\Support\Carbon::parse($selectedDate)]);
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

        $beli = $farm->purchases()->with('ingredient')->latest('tanggal')->latest('id')->limit(8)->get();
        $bahan = $farm->ingredients()->orderBy('nama')->get();
        $selisihTotal = $beli->sum(fn ($p) => ($p->harga_nyata - $p->harga_estimasi) * $p->kg);

        return view('catatan.index', compact('farm', 'rentang', 'logs', 'grafik', 'laporan', 'form', 'isEdit', 'riwayat', 'allLogs', 'yesterdayLog', 'existingLogs', 'beli', 'bahan', 'selisihTotal', 'harga', 'jumlahAyam', 'filterPeriode', 'cariTanggal'));
    }

    public function store(Request $r, AnomalyDetector $detector)
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

        return redirect()->route('catatan.index', ['tab' => 'form'])->with('ok', 'Catatan harian berhasil disimpan!');
    }

    public function destroy(DailyLog $log)
    {
        $this->own($log)->delete();

        return redirect()->route('catatan.index')->with('ok', 'Catatan dihapus.');
    }

    public function simpanPembelian(Request $r)
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

        $msg = 'Pembelian bahan berhasil dicatat!';
        if ($r->boolean('tambah_stok')) {
            $farmIng = $farm->farmIngredients()->where('ingredient_id', $ing->id)->first();
            if ($farmIng) {
                $farmIng->increment('stok_kg', $data['kg']);
                $farmIng->update([
                    'harga' => $data['harga_nyata'],
                    'harga_updated_at' => now(),
                ]);
                $msg = 'Pembelian dicatat & stok gudang berhasil ditambahkan!';
            }
        }

        return redirect()->route('catatan.index', ['tab' => 'beli'])->with('ok', $msg);
    }

    private function chart(string $type, array $labels, string $label, array $data, string $warna, ?float $rataRata = null): array
    {
        $n = count($data);
        $pointRadius = $type === 'line' ? array_fill(0, $n, 0) : 0;
        if ($type === 'line' && $n > 0) {
            $pointRadius[$n - 1] = 4;
        }
        $pointHoverRadius = $type === 'line' ? array_fill(0, $n, 4) : 0;
        if ($type === 'line' && $n > 0) {
            $pointHoverRadius[$n - 1] = 6;
        }

        $fillColor = match ($warna) {
            '#116a3a' => 'rgba(17, 106, 58, 0.12)',
            '#0e7490' => 'rgba(14, 116, 144, 0.12)',
            '#7c3aed' => 'rgba(124, 58, 237, 0.12)',
            default => $type === 'bar' ? $warna : $warna.'22',
        };

        $datasets = [
            [
                'label' => $label,
                'type' => $type,
                'data' => $data,
                'borderColor' => $warna,
                'backgroundColor' => $type === 'bar' ? $warna : $fillColor,
                'fill' => $type === 'line',
                'tension' => .35,
                'borderWidth' => 2.5,
                'pointRadius' => $pointRadius,
                'pointHoverRadius' => $pointHoverRadius,
                'pointBackgroundColor' => '#FFFFFF',
                'pointBorderColor' => $warna,
                'pointBorderWidth' => 2,
                'borderRadius' => $type === 'bar' ? 4 : 0,
                'maxBarThickness' => 24,
            ],
        ];

        if ($rataRata !== null && $n > 0) {
            $datasets[] = [
                'label' => 'Rata-rata',
                'type' => 'line',
                'data' => array_fill(0, $n, round($rataRata, 2)),
                'borderColor' => '#64748b',
                'borderWidth' => 1.5,
                'borderDash' => [4, 4],
                'pointRadius' => 0,
                'pointHoverRadius' => 0,
                'fill' => false,
            ];
        }

        return [
            'type' => $type,
            'labels' => $labels,
            'datasets' => $datasets,
            'zero' => false,
        ];
    }

    private function laporanPeriodik($farm, float $harga, int $rentang = 7): array
    {
        $days = max(1, $rentang - 1);
        $ini = $farm->logs()->where('tanggal', '>=', now()->subDays($days)->toDateString())->get();
        $lalu = $farm->logs()->whereBetween('tanggal', [
            now()->subDays($days * 2 + 1)->toDateString(),
            now()->subDays($days + 1)->toDateString(),
        ])->get();

        $hitung = function ($c) use ($harga) {
            $n = max(1, $c->count());
            $telurKg = $c->sum(fn ($l) => $l->telurKgEfektif());
            $pakan = (float) $c->sum('pakan_kg');
            $hargaTelurKg = (int) config('ransumku.harga_telur_default', 26000);
            $pendapatanTelur = (int) round($telurKg * $hargaTelurKg);
            $biayaPakan = (int) round($pakan * $harga);
            $marginKotor = $pendapatanTelur - $biayaPakan;

            return [
                'hari' => $c->count(),
                'pakan' => $pakan,
                'telur' => (int) $c->sum('telur_butir'),
                'telur_kg' => (float) round($telurKg, 2),
                'mati' => (int) $c->sum('ayam_mati'),
                'biaya_pakan' => $biayaPakan,
                'pendapatan_telur' => $pendapatanTelur,
                'margin_kotor' => $marginKotor,
                'biaya_lain' => (int) $c->sum('biaya_lain'),
                'biaya_kg_telur' => $telurKg > 0 ? $biayaPakan / $telurKg : 0,
                'rasio' => $c->sum('telur_butir') > 0 ? $pakan * 1000 / $c->sum('telur_butir') : 0, // gram pakan per butir
                'rata_telur' => $c->sum('telur_butir') / $n,
            ];
        };

        return ['ini' => $hitung($ini), 'lalu' => $hitung($lalu)];
    }

    private function laporanMingguan($farm, float $harga): array
    {
        return $this->laporanPeriodik($farm, $harga, 7);
    }
}
