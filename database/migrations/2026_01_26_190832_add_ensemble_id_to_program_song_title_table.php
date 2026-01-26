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
        Schema::table('program_song_title', function (Blueprint $table) {
            $table->foreignId('ensemble_id')->nullable()->after('song_title_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_song_title', function (Blueprint $table) {
            $table->dropForeign(['ensemble_id']);
            $table->dropColumn('ensemble_id');
        });
    }
};
