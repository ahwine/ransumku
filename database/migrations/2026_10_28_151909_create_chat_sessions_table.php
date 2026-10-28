<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $t->string('judul')->default('Obrolan Baru');
            $t->boolean('pinned')->default(false);
            $t->timestamps();
        });

        Schema::table('chat_messages', function (Blueprint $t) {
            $t->foreignId('chat_session_id')->nullable()->after('farm_id')->constrained('chat_sessions')->cascadeOnDelete();
        });

        // Migrasikan pesan obrolan yang sudah ada ke dalam sesi awal
        $farmIds = DB::table('chat_messages')->distinct()->pluck('farm_id');
        foreach ($farmIds as $farmId) {
            $firstMsg = DB::table('chat_messages')->where('farm_id', $farmId)->where('role', 'user')->value('isi');
            $judul = $firstMsg ? Str::limit($firstMsg, 35) : 'Konsultasi Kandang';

            $sessionId = DB::table('chat_sessions')->insertGetId([
                'farm_id' => $farmId,
                'judul' => $judul,
                'pinned' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('chat_messages')
                ->where('farm_id', $farmId)
                ->whereNull('chat_session_id')
                ->update(['chat_session_id' => $sessionId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $t) {
            $t->dropForeign(['chat_session_id']);
            $t->dropColumn('chat_session_id');
        });

        Schema::dropIfExists('chat_sessions');
    }
};
