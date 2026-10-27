<?php

namespace App\Console\Commands;

use App\Models\Farm;
use App\Models\User;
use App\Services\FarmBootstrap;
use Illuminate\Console\Command;

class IsiDataDemo extends Command
{
    protected $signature = 'ransumku:demo {email=demo@ransumku.test : Email pemilik kandang}';

    protected $description = 'Setel ulang data kandang ke data demo (14 hari catatan, 3 hari terakhir anomali)';

    public function handle(FarmBootstrap $boot): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (! $user) {
            $this->error('User tidak ditemukan. Jalankan: php artisan migrate --seed');

            return self::FAILURE;
        }

        $farm = $user->farm ?: Farm::create(['user_id' => $user->id, 'nama' => 'Kandang Maju Jaya', 'jumlah_ayam' => 200]);
        $boot->resetDemo($farm);
        $this->info('Data demo siap untuk '.$user->email);

        return self::SUCCESS;
    }
}
