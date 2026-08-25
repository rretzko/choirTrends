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
        Schema::create('song_title_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_title_id')->constrained()->cascadeOnDelete();
            $table->string('grade_level_context')->default('general');
            $table->string('voicing')->nullable();
            $table->json('difficulty_by_part')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('youtube_confidence')->nullable();
            $table->timestamp('youtube_verified_at')->nullable();
            $table->json('citation_urls')->nullable();
            $table->string('model_version')->nullable();
            $table->timestamp('assessed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['song_title_id', 'grade_level_context']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_title_assessments');
    }
};
