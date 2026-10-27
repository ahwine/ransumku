<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ration extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['pesan' => 'array', 'hasil' => 'array'];
    }

    public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
    public function standard(): BelongsTo { return $this->belongsTo(NutrientStandard::class, 'standard_id'); }
    public function items(): HasMany { return $this->hasMany(RationItem::class); }

    public function layak(): bool { return $this->status === 'layak'; }
}
