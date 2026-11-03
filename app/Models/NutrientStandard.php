<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutrientStandard extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_resmi' => 'boolean',
        'tahun' => 'integer',
        'protein_min' => 'float',
        'kalsium_min' => 'float',
        'kalsium_max' => 'float',
        'fosfor_min' => 'float',
        'fosfor_max' => 'float',
        'serat_max' => 'float',
        'energi_min' => 'integer',
    ];

    /** Bentuk array yang dipahami RationOptimizer. */
    public function toTarget(): array
    {
        return [
            'protein_min' => (float) $this->protein_min,
            'kalsium_min' => (float) $this->kalsium_min,
            'kalsium_max' => (float) $this->kalsium_max,
            'fosfor_min' => (float) $this->fosfor_min,
            'fosfor_max' => (float) $this->fosfor_max,
            'serat_max' => $this->serat_max !== null ? (float) $this->serat_max : null,
            'energi_min' => (float) $this->energi_min,
        ];
    }

    public function labelOtoritas(): string
    {
        if ($this->organisasi_sumber) {
            return $this->organisasi_sumber;
        }

        if ($this->is_resmi) {
            return 'Terverifikasi Resmi';
        }

        return 'Standar Mandiri';
    }

    public function badgeClass(): string
    {
        if (str_contains($this->organisasi_sumber ?? '', 'BSN') || str_contains($this->nama, 'SNI')) {
            return 'bg-emerald-50 text-emerald-800 border-emerald-200';
        }

        if (str_contains($this->organisasi_sumber ?? '', 'Lohmann') || str_contains($this->organisasi_sumber ?? '', 'Hendrix')) {
            return 'bg-blue-50 text-blue-800 border-blue-200';
        }

        if ($this->is_resmi) {
            return 'bg-teal-50 text-teal-800 border-teal-200';
        }

        return 'bg-surface text-muted border-line';
    }
}
