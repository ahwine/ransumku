<?php

namespace App\Models;

use App\Support\Fmt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NutrientStandard extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_resmi' => 'boolean',
        'verified' => 'boolean',
        'tahun' => 'integer',
        'protein_min' => 'float',
        'kalsium_min' => 'float',
        'kalsium_max' => 'float',
        'fosfor_min' => 'float',
        'fosfor_max' => 'float',
        'serat_max' => 'float',
        'energi_min' => 'integer',
        'parameter_tambahan' => 'array',
    ];

    /**
     * Bentuk array target zat gizi yang dipahami RationOptimizer.
     * Nilai null menandakan parameter tersebut belum memiliki angka terverifikasi di sumber.
     *
     * @return array{
     *     protein_min: float|null,
     *     kalsium_min: float|null,
     *     kalsium_max: float|null,
     *     fosfor_min: float|null,
     *     fosfor_max: float|null,
     *     serat_max: float|null,
     *     energi_min: float|null
     * }
     */
    public function toTarget(): array
    {
        return [
            'protein_min' => $this->protein_min !== null ? (float) $this->protein_min : null,
            'kalsium_min' => $this->kalsium_min !== null ? (float) $this->kalsium_min : null,
            'kalsium_max' => $this->kalsium_max !== null ? (float) $this->kalsium_max : null,
            'fosfor_min' => $this->fosfor_min !== null ? (float) $this->fosfor_min : null,
            'fosfor_max' => $this->fosfor_max !== null ? (float) $this->fosfor_max : null,
            'serat_max' => $this->serat_max !== null ? (float) $this->serat_max : null,
            'energi_min' => $this->energi_min !== null ? (float) $this->energi_min : null,
        ];
    }

    public function rations(): HasMany
    {
        return $this->hasMany(Ration::class, 'standard_id');
    }

    public function hasUsableTargets(): bool
    {
        foreach ($this->toTarget() as $val) {
            if ($val !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apakah standar ini berasal dari salinan sumber sekunder yang belum dicek ke dokumen primer.
     */
    public function isSumberSekunder(): bool
    {
        $extra = is_array($this->parameter_tambahan) ? $this->parameter_tambahan : [];
        if (($extra['status_verifikasi'] ?? null) === 'sumber_sekunder') {
            return true;
        }

        $sourceText = strtolower((string) ($this->source ?? $this->sumber ?? ''));

        return str_contains($sourceText, 'sumber sekunder');
    }

    /**
     * Daftar parameter nutrisi utama yang bernilai null (tidak dibatasi oleh standar ini).
     *
     * @return array<int, string>
     */
    public function unconstrainedParameters(): array
    {
        $params = [];

        if ($this->protein_min === null) {
            $params[] = 'Protein kasar';
        }

        if ($this->energi_min === null) {
            $params[] = 'Energi metabolis';
        }

        if ($this->kalsium_min === null && $this->kalsium_max === null) {
            $params[] = 'Kalsium';
        }

        if ($this->fosfor_min === null && $this->fosfor_max === null) {
            $params[] = 'Fosfor';
        }

        if ($this->serat_max === null) {
            $params[] = 'Serat kasar';
        }

        return $params;
    }

    public function formatProtein(): string
    {
        if ($this->protein_min !== null) {
            return 'Min ' . Fmt::num($this->protein_min, 1) . '%';
        }

        return (string) ($this->parameter_tambahan['protein_label'] ?? 'Belum ada data');
    }

    public function formatEnergi(): string
    {
        if ($this->energi_min !== null) {
            return 'Min ' . Fmt::num($this->energi_min, 0) . ' kkal';
        }

        return (string) ($this->parameter_tambahan['energi_label'] ?? 'Tidak di sumber');
    }

    public function formatKalsium(): string
    {
        if ($this->kalsium_min !== null && $this->kalsium_max !== null) {
            return Fmt::num($this->kalsium_min, 2) . '–' . Fmt::num($this->kalsium_max, 2) . '%';
        }

        if ($this->kalsium_min !== null) {
            return 'Min ' . Fmt::num($this->kalsium_min, 2) . '%';
        }

        if ($this->kalsium_max !== null) {
            return 'Maks ' . Fmt::num($this->kalsium_max, 2) . '%';
        }

        return 'Belum ada data';
    }

    public function formatFosfor(): string
    {
        if ($this->fosfor_min !== null && $this->fosfor_max !== null) {
            return Fmt::num($this->fosfor_min, 2) . '–' . Fmt::num($this->fosfor_max, 2) . '%';
        }

        if ($this->fosfor_min !== null) {
            return 'Min ' . Fmt::num($this->fosfor_min, 2) . '%';
        }

        if ($this->fosfor_max !== null) {
            return 'Maks ' . Fmt::num($this->fosfor_max, 2) . '%';
        }

        return (string) ($this->parameter_tambahan['fosfor_label'] ?? 'Belum ada data');
    }

    public function formatSerat(): string
    {
        if ($this->serat_max !== null) {
            return 'Maks ' . Fmt::num($this->serat_max, 1) . '%';
        }

        return 'Tidak di sumber';
    }

    /**
     * Ringkasan parameter tambahan dari sumber yang tersedia (air, abu, lemak, asam amino, P tersedia, aflatoksin).
     *
     * @return array<int, string>
     */
    public function ringkasanParameterTambahan(): array
    {
        $extra = is_array($this->parameter_tambahan) ? $this->parameter_tambahan : [];
        $badges = [];

        if (isset($extra['asupan_g_hari'])) {
            $badges[] = 'Asupan ' . Fmt::num((float) $extra['asupan_g_hari'], 0) . ' g/ekor/hari';
        }
        if (isset($extra['air_max'])) {
            $badges[] = 'Air maks ' . Fmt::num((float) $extra['air_max'], 1) . '%';
        }
        if (isset($extra['abu_max'])) {
            $badges[] = 'Abu maks ' . Fmt::num((float) $extra['abu_max'], 1) . '%';
        }
        if (isset($extra['lemak_min'])) {
            $badges[] = 'Lemak min ' . Fmt::num((float) $extra['lemak_min'], 1) . '%';
        }
        if (isset($extra['fosfor_tersedia_min'])) {
            $badges[] = 'P tersedia ' . Fmt::num((float) $extra['fosfor_tersedia_min'], 2) . '%';
        }
        if (isset($extra['lisin_min'])) {
            $badges[] = 'Lisin ' . Fmt::num((float) $extra['lisin_min'], 2) . '%';
        }
        if (isset($extra['metionin_min'])) {
            $badges[] = 'Metionin ' . Fmt::num((float) $extra['metionin_min'], 2) . '%';
        }
        if (isset($extra['aflatoksin_max_ug_kg'])) {
            $badges[] = 'Aflatoksin maks ' . Fmt::num((float) $extra['aflatoksin_max_ug_kg'], 0) . ' ug/kg';
        }

        return $badges;
    }

    public function labelOtoritas(): string
    {
        if ($this->organisasi_sumber) {
            return $this->organisasi_sumber;
        }

        return 'Acuan Mandiri';
    }

    public function labelFase(): string
    {
        return match ($this->kategori_fase) {
            'pre-starter' => 'Pre-Starter (1–4 Minggu)',
            'starter' => 'Starter (5–10 Minggu)',
            'grower' => 'Dara / Grower (11–16 Minggu)',
            'produksi' => 'Masa Bertelur (Layer)',
            default => ucfirst((string) ($this->kategori_fase ?? 'Layer')),
        };
    }

    public function badgeClass(): string
    {
        $org = $this->organisasi_sumber ?? '';

        if (str_contains($org, 'BSN') || str_contains($this->nama, 'SNI')) {
            return 'bg-emerald-50 text-emerald-800 border-emerald-200';
        }

        if (str_contains($org, 'Lohmann') || str_contains($org, 'Hendrix')) {
            return 'bg-blue-50 text-blue-800 border-blue-200';
        }

        if ($this->is_resmi) {
            return 'bg-teal-50 text-teal-800 border-teal-200';
        }

        return 'bg-surface text-muted border-line';
    }
}
