<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function farm(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Farm::class);
    }

    public function farms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Farm::class);
    }

    /** Kandang aktif saat ini (dari sesi atau kandang pertama milik user). */
    public function activeFarm(): ?Farm
    {
        $activeId = session('active_farm_id');
        if ($activeId) {
            $farm = $this->farms()->find($activeId);
            if ($farm) {
                return $farm;
            }
        }

        $farm = $this->farms()->first();
        if ($farm) {
            session(['active_farm_id' => $farm->id]);
            return $farm;
        }

        return null;
    }
}
