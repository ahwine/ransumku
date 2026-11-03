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
        Schema::table('nutrient_standards', function (Blueprint $table): void {
            $table->decimal('protein_min', 6, 2)->nullable()->change();
            $table->decimal('kalsium_min', 6, 2)->nullable()->change();
            $table->decimal('kalsium_max', 6, 2)->nullable()->change();
            $table->decimal('fosfor_min', 6, 2)->nullable()->change();
            $table->decimal('fosfor_max', 6, 2)->nullable()->change();
            $table->unsignedInteger('energi_min')->nullable()->change();

            $table->string('edition_year', 60)->nullable()->after('tahun');
            $table->text('source')->nullable()->after('sumber');
            $table->boolean('verified')->default(false)->after('is_resmi');
            $table->string('dataset_version', 30)->nullable()->after('organisasi_sumber');
            $table->json('parameter_tambahan')->nullable()->after('dataset_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nutrient_standards', function (Blueprint $table): void {
            $table->dropColumn([
                'edition_year',
                'source',
                'verified',
                'dataset_version',
                'parameter_tambahan',
            ]);
        });
    }
};

