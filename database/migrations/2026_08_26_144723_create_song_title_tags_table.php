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
        Schema::create('song_title_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('song_title_id')->constrained()->cascadeOnDelete();
            $table->string('tag');
            $table->string('authorship_type');
            $table->foreignId('authorship_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('repertoire_query_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['song_title_id', 'tag']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('song_title_tags');
    }
};
