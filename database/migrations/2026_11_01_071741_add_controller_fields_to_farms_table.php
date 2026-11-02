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
        Schema::table('farms', function (Blueprint $table) {
            $table->boolean('controller_siap')->default(false)->after('sensor_updated_at'); // false = MERAH (Standby), true = BIRU (Siap)
            $table->string('controller_mode', 20)->default('otomatis')->after('controller_siap'); // otomatis / manual
            $table->boolean('relay_kipas')->default(false)->after('controller_mode');
            $table->boolean('relay_misting')->default(false)->after('relay_kipas');
            $table->boolean('relay_lampu')->default(false)->after('relay_misting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropColumn([
                'controller_siap',
                'controller_mode',
                'relay_kipas',
                'relay_misting',
                'relay_lampu',
            ]);
        });
    }
};
