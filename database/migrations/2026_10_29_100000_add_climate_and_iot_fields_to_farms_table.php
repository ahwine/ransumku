<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $t) {
            $t->string('lokasi_kota')->default('Surabaya')->after('konsumsi_per_ekor');
            $t->decimal('latitude', 10, 7)->default(-7.2575)->after('lokasi_kota');
            $t->decimal('longitude', 10, 7)->default(112.7521)->after('latitude');
            $t->decimal('sensor_suhu', 5, 2)->nullable()->after('longitude');
            $t->decimal('sensor_kelembaban', 5, 2)->nullable()->after('sensor_suhu');
            $t->timestamp('sensor_updated_at')->nullable()->after('sensor_kelembaban');
            $t->string('iot_token', 64)->nullable()->after('sensor_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $t) {
            $t->dropColumn([
                'lokasi_kota',
                'latitude',
                'longitude',
                'sensor_suhu',
                'sensor_kelembaban',
                'sensor_updated_at',
                'iot_token',
            ]);
        });
    }
};

