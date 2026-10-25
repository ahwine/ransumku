<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $guarded = [];

    public function farm(): BelongsTo { return $this->belongsTo(Farm::class); }
    public function farmIngredients(): HasMany { return $this->hasMany(FarmIngredient::class); }
}
