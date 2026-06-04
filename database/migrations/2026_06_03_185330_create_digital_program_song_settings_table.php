<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_program_song_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_title_id')->constrained()->cascadeOnDelete();
            $table->boolean('show_lyrics')->default(false);
            $table->unique(['digital_program_id', 'song_title_id'], 'dp_song_settings_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_program_song_settings');
    }
};
