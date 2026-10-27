<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RationItem extends Model
{
    protected $guarded = [];

    public function ration(): BelongsTo { return $this->belongsTo(Ration::class); }
    public function ingredient(): BelongsTo { return $this->belongsTo(Ingredient::class); }
}
