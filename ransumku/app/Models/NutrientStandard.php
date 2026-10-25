<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutrientStandard extends Model
{
    protected $guarded = [];

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
}
