<?php

namespace Database\Seeders;

use App\Models\Farm;
use App\Models\User;
use App\Services\FarmBootstrap;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $boot = app(FarmBootstrap::class);
        $boot->seedTemplates();

        // Akun demo: demo@ransumku.test / password
        $user = User::firstOrCreate(
            ['email' => 'demo@ransumku.test'],
            ['name' => 'Pak Budi', 'password' => 'password']
        );
        $farm = $user->farm ?: Farm::create(['user_id' => $user->id, 'nama' => 'Kandang Maju Jaya', 'jumlah_ayam' => 200, 'fase' => 'produksi']);

        $boot->resetDemo($farm);
    }
}
