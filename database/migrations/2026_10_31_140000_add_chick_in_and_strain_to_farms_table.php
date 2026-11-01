<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $t) {
            $t->date('tgl_chick_in')->nullable()->after('fase');
            $t->unsignedInteger('umur_awal_minggu')->default(18)->after('tgl_chick_in');
            $t->string('strain_ayam', 60)->default('Lohmann Brown')->after('umur_awal_minggu');
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $t) {
            $t->dropColumn(['tgl_chick_in', 'umur_awal_minggu', 'strain_ayam']);
        });
    }
};
