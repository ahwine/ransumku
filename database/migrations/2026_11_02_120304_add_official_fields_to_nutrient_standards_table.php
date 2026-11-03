<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nutrient_standards', function (Blueprint $table) {
            $table->string('kode', 50)->nullable()->after('nama');
            $table->string('kategori_fase', 30)->nullable()->after('kode'); // starter | grower | produksi | afkir | all
            $table->boolean('is_resmi')->default(false)->after('sumber');
            $table->string('organisasi_sumber', 100)->nullable()->after('is_resmi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nutrient_standards', function (Blueprint $table) {
            $table->dropColumn(['kode', 'kategori_fase', 'is_resmi', 'organisasi_sumber']);
        });
    }
};
