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
        Schema::table('song_titles', function (Blueprint $table) {
            $table->dropUnique('song_titles_song_title_unique');
            $table->unique(['song_title', 'composer_id', 'arranger_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('song_titles', function (Blueprint $table) {
            $table->dropUnique(['song_title', 'composer_id', 'arranger_id']);
            $table->unique('song_title');
        });
    }
};
