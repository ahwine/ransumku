<?php

namespace App\Http\Controllers;

use App\Models\Ration;
use App\Services\AiAssistant;
use App\Services\FeedConversionService;
use App\Services\RationCalculator;
use App\Services\RationSafetyValidator;
use Illuminate\Http\Request;

class RationController extends Controller
{
    public function index()
    {
        $rations = $this->farm()->rations()->with('items')->latest('id')->get();

        return view('ransum.index', compact('rations'));
    }

    public function create(Request $r)
    {
        $farm = $this->farm();
        $fis = $farm->farmIngredients()->with('ingredient')->get()->sortByDesc('aktif')->values();
        $standards = $farm->standards()->get();
        $konsumsi = $farm->konsumsi_per_ekor;

        return view('ransum.create', compact('farm', 'fis', 'standards', 'konsumsi'));
    }

    public function store(Request $r, RationCalculator $calc)
    {
        $farm = $this->farm();

        $data = $r->validate([
            'mode' => 'required|in:kg,hari',
            'total_kg' => 'required_if:mode,kg|nullable|numeric|min:1|max:100000',
            'jumlah_hari' => 'required_if:mode,hari|nullable|integer|min:1|max:365',
            'jumlah_ayam' => 'required_if:mode,hari|nullable|integer|min:1',
            'konsumsi' => 'required_if:mode,hari|nullable|numeric|min:0.01|max:1',
            'standard_id' => 'required|integer',
            'bahan' => 'required|array',
            'bahan.*.harga' => 'nullable|numeric|min:0',
            'bahan.*.stok' => 'nullable|numeric|min:0',
            'bahan.*.min_persen' => 'nullable|numeric|min:0|max:100',
            'bahan.*.max_persen' => 'nullable|numeric|min:0|max:100',
            'bahan.*.aktif' => 'nullable|boolean',
        ], [
            'total_kg.required_if' => 'Isi jumlah campuran (kg).',
            'jumlah_hari.required_if' => 'Isi jumlah hari.',
        ]);

        $std = $farm->standards()->findOrFail($data['standard_id']);

        // Simpan harga & stok yang diisi peternak. harga_updated_at hanya berubah bila harga berubah.
        $aktif = 0;
        foreach ($farm->farmIngredients()->get() as $fi) {
            $in = $data['bahan'][$fi->id] ?? null;
            if (! $in) {
                continue;
            }
            $harga = (int) round((float) ($in['harga'] ?? $fi->harga));
            $upd = [
                'stok_kg' => (float) ($in['stok'] ?? $fi->stok_kg),
                'min_persen' => isset($in['min_persen']) && $in['min_persen'] !== '' ? (float) $in['min_persen'] : $fi->min_persen,
                'max_persen' => isset($in['max_persen']) && $in['max_persen'] !== '' ? (float) $in['max_persen'] : $fi->max_persen,
                'aktif' => ! empty($in['aktif']),
            ];
            if ($harga !== (int) $fi->harga) {
                $upd['harga'] = $harga;
                $upd['harga_updated_at'] = now();
            }
            $fi->update($upd);
            $aktif += $upd['aktif'] ? 1 : 0;
        }

        if ($aktif === 0) {
            return back()->withInput()->withErrors(['bahan' => 'Pilih minimal satu bahan.']);
        }

        if ($data['mode'] === 'hari') {
            $total = round($data['jumlah_hari'] * $data['jumlah_ayam'] * $data['konsumsi'], 2);
            $hari = (int) $data['jumlah_hari'];
        } else {
            $total = (float) $data['total_kg'];
            $hari = null;
        }

        $ration = $calc->hitung($farm, $total, $hari, $std);

        return redirect()->route('ransum.show', $ration);
    }

    public function show(Request $r, Ration $ration, FeedConversionService $converter, RationSafetyValidator $safetyValidator)
    {
        $this->own($ration);
        $ration->load('items.ingredient', 'standard');

        // Skala ulang resep (disimpan dalam persen) untuk jumlah lain
        $skala = (float) $r->query('kg', $ration->total_kg);
        $skala = max(1, min($skala, 100000));

        $baris = $ration->items->map(fn ($i) => [
            'id' => $i->ingredient_id,
            'nama' => $i->nama,
            'persen' => (float) $i->persen,
            'kg' => $i->persen / 100 * $skala,
            'bk' => (float) ($i->bk ?: ($i->ingredient?->bk ?? 88.0)),
            'harga' => $i->harga_saat_hitung,
            'biaya' => $i->persen / 100 * $skala * $i->harga_saat_hitung,
            'min_persen' => $i->ingredient?->min_persen,
            'max_persen' => $i->ingredient?->max_persen,
            'catatan_keamanan' => $i->ingredient?->catatan_keamanan,
        ]);

        $asFed = $converter->convertToAsFed($baris->all());

        // Kapasitas batch mixer (opsional query param: mixer_kg, default 100 kg)
        $mixerKg = max(1.0, min((float) $r->query('mixer_kg', 100.0), 10000.0));
        $mixingSheet = $converter->hitungMixingSheet($asFed['items'], $mixerKg);

        // Validasi keamanan pakan
        $giziMap = [];
        if (! empty($ration->hasil['gizi'])) {
            foreach ($ration->hasil['gizi'] as $g) {
                $lbl = strtolower(explode(' ', $g['label'])[0]);
                $giziMap[$lbl] = (float) $g['nilai'];
            }
        }
        $keamanan = $ration->hasil['keamanan'] ?? $safetyValidator->validate($baris->all(), $giziMap);

        $hargaUsang = $this->farm()->farmIngredients()->where('aktif', true)->get()->contains(fn ($fi) => $fi->hargaUsang());

        return view('ransum.show', compact('ration', 'baris', 'skala', 'hargaUsang', 'asFed', 'mixerKg', 'mixingSheet', 'keamanan'));
    }

    /** Hitung ulang memakai harga & stok TERBARU, jumlah dan acuan yang sama. */
    public function hitungUlang(Ration $ration, RationCalculator $calc)
    {
        $this->own($ration);
        $std = $ration->standard ?? $this->farm()->standards()->first();
        $baru = $calc->hitung($this->farm(), (float) $ration->total_kg, $ration->jumlah_hari, $std);

        return redirect()->route('ransum.show', $baru)->with('ok', 'Dihitung ulang dengan harga dan stok terbaru.');
    }

    public function jelaskan(Ration $ration, AiAssistant $ai)
    {
        $this->own($ration);
        $ration->update(['penjelasan' => $ai->jelaskan($ration)]);

        return redirect()->route('ransum.show', $ration)->withFragment('penjelasan');
    }

    public function destroy(Ration $ration)
    {
        $this->own($ration)->delete();

        return redirect()->route('ransum.index')->with('ok', 'Resep dihapus.');
    }
}
