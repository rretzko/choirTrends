<?php

declare(strict_types=1);

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
        Schema::create('song_title_difficulty_observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_title_id')->constrained()->cascadeOnDelete();
            $table->string('voice_part');
            $table->string('difficulty_label');
            $table->unsignedTinyInteger('difficulty_value');
            $table->string('authorship_type');
            $table->foreignId('authorship_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('repertoire_query_id')->nullable()->constrained()->nullOnDelete();
            $table->string('citation_url')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamps();

            $table->index(['song_title_id', 'voice_part'], 'song_title_difficulty_observations_song_voice_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_title_difficulty_observations');
    }
};
