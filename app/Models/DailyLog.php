<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyLog extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }

    /** Berat telur: pakai input peternak, kalau kosong perkirakan 62 g/butir. */
    public function telurKgEfektif(): float
    {
        return $this->telur_kg !== null ? (float) $this->telur_kg : $this->telur_butir * 0.062;
    }
}
