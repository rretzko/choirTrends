<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_song_lyrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('song_title_id')->constrained()->cascadeOnDelete();
            $table->longText('content');
            $table->enum('source', ['manual', 'uploaded'])->default('manual');
            $table->timestamps();

            $table->unique(['user_id', 'song_title_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE user_song_lyrics ADD FULLTEXT INDEX user_song_lyrics_content_fulltext (content)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_song_lyrics');
    }
};
