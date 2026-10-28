<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->decimal('bk', 5, 2)->default(88.00)->after('serat');
            $table->decimal('min_persen', 5, 2)->default(0.00)->after('harga_default');
            $table->decimal('max_persen', 5, 2)->default(100.00)->after('min_persen');
            $table->string('catatan_keamanan')->nullable()->after('catatan');
        });

        Schema::table('farm_ingredients', function (Blueprint $table) {
            $table->decimal('min_persen', 5, 2)->nullable()->after('stok_kg');
            $table->decimal('max_persen', 5, 2)->nullable()->after('min_persen');
        });

        Schema::table('ration_items', function (Blueprint $table) {
            $table->decimal('bk', 5, 2)->default(88.00)->after('persen');
            $table->decimal('kg_as_fed', 10, 3)->nullable()->after('bk');
            $table->decimal('persen_as_fed', 7, 3)->nullable()->after('kg_as_fed');
        });
    }

    public function down(): void
    {
        Schema::table('ration_items', function (Blueprint $table) {
            $table->dropColumn(['bk', 'kg_as_fed', 'persen_as_fed']);
        });

        Schema::table('farm_ingredients', function (Blueprint $table) {
            $table->dropColumn(['min_persen', 'max_persen']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn(['bk', 'min_persen', 'max_persen', 'catatan_keamanan']);
        });
    }
};
