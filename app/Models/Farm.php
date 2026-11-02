<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Farm extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tgl_chick_in' => 'date',
            'controller_siap' => 'boolean',
            'relay_kipas' => 'boolean',
            'relay_misting' => 'boolean',
            'relay_lampu' => 'boolean',
            'relay_pakan' => 'boolean',
            'controller_settings' => 'array',
        ];
    }

    /**
     * Pengaturan ambang batas otomatisasi mikroklimat aktuator kandang.
     *
     * @return array{
     *     kipas: array{otomatis: bool, suhu_nyala: float, thi_nyala: float},
     *     misting: array{otomatis: bool, thi_nyala: float, rh_maksimal: float},
     *     lampu: array{otomatis: bool, jam_pagi_mulai: string, jam_pagi_selesai: string, jam_sore_mulai: string, jam_sore_selesai: string},
     *     pakan: array{otomatis: bool, mode: string, durasi_detik: int, jam_pagi: string, jam_sore: string}
     * }
     */
    public function getSettingsKontroler(): array
    {
        $defaults = [
            'kipas' => [
                'otomatis' => false,
                'suhu_nyala' => 31.0,
                'thi_nyala' => 78.0,
            ],
            'misting' => [
                'otomatis' => false,
                'thi_nyala' => 80.0,
                'rh_maksimal' => 75.0,
            ],
            'lampu' => [
                'otomatis' => false,
                'jam_pagi_mulai' => '04:00',
                'jam_pagi_selesai' => '07:00',
                'jam_sore_mulai' => '18:00',
                'jam_sore_selesai' => '21:00',
            ],
            'pakan' => [
                'otomatis' => false,
                'mode' => 'shuttle', // 'shuttle' (ujung-ke-ujung) atau 'bolak_balik' (round-trip)
                'durasi_detik' => 4,
                'jam_pagi' => '07:00',
                'jam_sore' => '15:30',
            ],
        ];

        $current = $this->controller_settings ?? [];

        return array_replace_recursive($defaults, is_array($current) ? $current : []);
    }

    /** Umur ayam saat ini dalam satuan minggu. */
    public function umurMinggu(): int
    {
        if ($this->tgl_chick_in) {
            $weeks = (int) $this->tgl_chick_in->diffInWeeks(now());
            return (int) ($this->umur_awal_minggu ?? 18) + $weeks;
        }

        return 28;
    }

    /**
     * Detail fase umur biologis ayam petelur (Layer standard).
     *
     * @return array{nama: string, kode: string, standar_hdp: string, standar_pakan: string, badge_class: string}
     */
    public function faseUmurDetail(): array
    {
        $minggu = $this->umurMinggu();

        if ($minggu < 18) {
            return [
                'nama' => 'Grower / Pullet',
                'kode' => 'pullet',
                'standar_hdp' => '< 50%',
                'standar_pakan' => '75 - 90 gr/ekor',
                'badge_class' => 'bg-amber-100 text-amber-800 border-amber-300',
            ];
        }

        if ($minggu <= 24) {
            return [
                'nama' => 'Awal Bertelur',
                'kode' => 'awal',
                'standar_hdp' => '50 - 85%',
                'standar_pakan' => '95 - 110 gr/ekor',
                'badge_class' => 'bg-blue-100 text-blue-800 border-blue-300',
            ];
        }

        if ($minggu <= 38) {
            return [
                'nama' => 'Puncak Produksi (Peak)',
                'kode' => 'puncak',
                'standar_hdp' => '90 - 95%',
                'standar_pakan' => '110 - 118 gr/ekor',
                'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            ];
        }

        if ($minggu <= 60) {
            return [
                'nama' => 'Pasca Puncak (Post-Peak)',
                'kode' => 'pasca_puncak',
                'standar_hdp' => '82 - 89%',
                'standar_pakan' => '115 - 120 gr/ekor',
                'badge_class' => 'bg-teal-100 text-teal-800 border-teal-300',
            ];
        }

        return [
            'nama' => 'Fase Akhir (Afkir)',
            'kode' => 'afkir',
            'standar_hdp' => '70 - 80%',
            'standar_pakan' => '115 - 122 gr/ekor',
            'badge_class' => 'bg-slate-100 text-slate-800 border-slate-300',
        ];
    }

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
