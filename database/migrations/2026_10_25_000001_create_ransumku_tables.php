<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farms', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('nama');
            $t->unsignedInteger('jumlah_ayam')->default(0);
            $t->string('fase')->default('produksi'); // starter | grower | produksi
            $t->decimal('konsumsi_per_ekor', 6, 3)->default(0.110); // kg/ekor/hari (perkiraan)
            $t->timestamps();
        });

        // farm_id NULL = bahan "template" bawaan; disalin ke kandang saat onboarding
        Schema::create('ingredients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('nama');
            $t->decimal('protein', 6, 2)->default(0);
            $t->decimal('kalsium', 6, 2)->default(0);
            $t->decimal('fosfor', 6, 2)->default(0);
            $t->decimal('serat', 6, 2)->default(0);
            $t->unsignedInteger('energi')->default(0); // kkal/kg
            $t->unsignedInteger('harga_default')->default(0);
            $t->string('sumber')->nullable();
            $t->text('catatan')->nullable();
            $t->timestamps();
        });

        Schema::create('farm_ingredients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('harga')->default(0); // Rp/kg, INPUT peternak
            $t->decimal('stok_kg', 10, 2)->default(0); // batas maksimal pemakaian
            $t->boolean('aktif')->default(true);
            $t->timestamp('harga_updated_at')->nullable();
            $t->timestamps();
            $t->unique(['farm_id', 'ingredient_id']);
        });

        Schema::create('nutrient_standards', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('nama');
            $t->unsignedSmallInteger('tahun')->nullable();
            $t->decimal('protein_min', 6, 2);
            $t->decimal('kalsium_min', 6, 2);
            $t->decimal('kalsium_max', 6, 2);
            $t->decimal('fosfor_min', 6, 2);
            $t->decimal('fosfor_max', 6, 2);
            $t->decimal('serat_max', 6, 2)->nullable();
            $t->unsignedInteger('energi_min');
            $t->string('sumber')->nullable();
            $t->timestamps();
        });

        Schema::create('rations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->foreignId('standard_id')->nullable()->constrained('nutrient_standards')->nullOnDelete();
            $t->decimal('total_kg', 10, 2);
            $t->unsignedInteger('jumlah_hari')->nullable();
            $t->unsignedBigInteger('total_biaya')->default(0);
            $t->unsignedInteger('biaya_per_kg')->default(0);
            $t->string('status')->default('layak'); // layak | tidak_layak
            $t->json('pesan')->nullable();
            $t->json('hasil')->nullable(); // cek gizi, sisa stok, daftar belanja
            $t->text('penjelasan')->nullable();
            $t->timestamps();
        });

        Schema::create('ration_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ration_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ingredient_id')->nullable()->constrained()->nullOnDelete();
            $t->string('nama');
            $t->decimal('kg', 10, 3);
            $t->decimal('persen', 7, 3);
            $t->unsignedInteger('harga_saat_hitung')->default(0);
            $t->unsignedBigInteger('biaya')->default(0);
            $t->timestamps();
        });

        Schema::create('daily_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->date('tanggal');
            $t->decimal('pakan_kg', 8, 2)->default(0);
            $t->unsignedInteger('telur_butir')->default(0);
            $t->decimal('telur_kg', 8, 2)->nullable();
            $t->unsignedInteger('ayam_mati')->default(0);
            $t->unsignedBigInteger('biaya_lain')->default(0);
            $t->text('catatan')->nullable();
            $t->timestamps();
            $t->unique(['farm_id', 'tanggal']);
        });

        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $t->decimal('kg', 10, 2);
            $t->unsignedInteger('harga_nyata'); // Rp/kg
            $t->unsignedInteger('harga_estimasi')->default(0); // Rp/kg yang dipakai saat menghitung
            $t->date('tanggal');
            $t->timestamps();
        });

        Schema::create('alerts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->string('jenis');
            $t->text('pesan');
            $t->string('level')->default('waspada'); // perhatian | waspada
            $t->date('tanggal_data')->nullable();
            $t->boolean('dibaca')->default(false);
            $t->timestamps();
            $t->unique(['farm_id', 'jenis', 'tanggal_data']);
        });

        Schema::create('chat_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->string('role'); // user | assistant
            $t->text('isi');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['chat_messages', 'alerts', 'purchases', 'daily_logs', 'ration_items', 'rations', 'nutrient_standards', 'farm_ingredients', 'ingredients', 'farms'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
