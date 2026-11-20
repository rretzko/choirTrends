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
        Schema::table('programs', function (Blueprint $table) {
            $table->string('youtube_url')->nullable()->after('video_uploaded_at');
        });

        Schema::table('program_song_title', function (Blueprint $table) {
            $table->string('youtube_url')->nullable()->after('video_uploaded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('youtube_url');
        });

        Schema::table('program_song_title', function (Blueprint $table) {
            $table->dropColumn('youtube_url');
        });
    }
};
