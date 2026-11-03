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

    public function layak(): bool
    {
        return $this->status === 'layak';
    }

    /**
     * Nama acuan standar gizi yang digunakan saat formulasi (tetap aman meski standar telah dihapus).
     */
    public function namaStandar(): string
    {
        if ($this->standard) {
            return $this->standard->nama;
        }

        if (! empty($this->hasil['standar_nama'])) {
            $suffix = ! empty($this->hasil['standar_terhapus']) ? ' (arsip — dihapus dari katalog)' : '';

            return (string) $this->hasil['standar_nama'] . $suffix;
        }

        return 'Acuan terdahulu (dihapus dari katalog)';
    }

    /**
     * Daftar parameter nutrisi utama yang bernilai null (tidak dibatasi oleh standar yang dipilih).
     *
     * @return array<int, string>
     */
    public function parameterTidakDibatasi(): array
    {
        if ($this->standard) {
            return $this->standard->unconstrainedParameters();
        }

        $saved = $this->hasil['parameter_tidak_dibatasi'] ?? [];

        return is_array($saved) ? array_values($saved) : [];
    }
}
