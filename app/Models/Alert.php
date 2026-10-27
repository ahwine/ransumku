<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal_data' => 'date', 'dibaca' => 'boolean'];
    }

    public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
}
