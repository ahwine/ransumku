<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $guarded = [];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function farmIngredients(): HasMany { return $this->hasMany(FarmIngredient::class); }
    public function ingredients(): HasMany { return $this->hasMany(Ingredient::class); }
    public function standards(): HasMany { return $this->hasMany(NutrientStandard::class); }
    public function rations(): HasMany { return $this->hasMany(Ration::class); }
    public function logs(): HasMany { return $this->hasMany(DailyLog::class); }
    public function purchases(): HasMany { return $this->hasMany(Purchase::class); }
    public function alerts(): HasMany { return $this->hasMany(Alert::class); }
    public function chatSessions(): HasMany { return $this->hasMany(ChatSession::class); }
    public function chats(): HasMany { return $this->hasMany(ChatMessage::class); }

    /** Ransum terakhir yang berhasil dihitung. */
    public function lastRation(): ?Ration
    {
        return $this->rations()->where('status', 'layak')->latest('id')->first();
    }

    /** Estimasi biaya pakan per kg: dari ransum terakhir, atau 0 bila belum ada. */
    public function hargaPakanPerKg(): float
    {
        return (float) ($this->lastRation()?->biaya_per_kg ?? 0);
    }

    /** Tanggal pembaruan harga paling lama di antara bahan aktif (untuk peringatan > 7 hari). */
    public function hargaTerlamaDiperbarui(): ?\Illuminate\Support\Carbon
    {
        $min = $this->farmIngredients()->where('aktif', true)->min('harga_updated_at');
        return $min ? \Illuminate\Support\Carbon::parse($min) : null;
    }
}
