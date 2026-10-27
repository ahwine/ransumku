<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FarmIngredient extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['harga_updated_at' => 'datetime', 'aktif' => 'boolean'];
    }

    public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
    public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }

    /** Harga dianggap usang bila > 7 hari tidak diperbarui. */
    public function hargaUsang(): bool
    {
        return ! $this->harga_updated_at || $this->harga_updated_at->lt(now()->subDays(7));
    }
}
