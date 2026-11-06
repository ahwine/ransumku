<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    /**
     * Menghitung selisih total belanja nyata terhadap harga acuan formulasi (Rp).
     */
    public function selisih(): float
    {
        return (float) (($this->harga_nyata - $this->harga_estimasi) * $this->kg);
    }
}
