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
            $table->string('video_path')->nullable()->after('director_name');
            $table->string('video_visibility')->nullable()->default('Private')->after('video_path');
            $table->timestamp('video_uploaded_at')->nullable()->after('video_visibility');
        });

        Schema::table('program_song_title', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('sort_order');
            $table->string('video_visibility')->nullable()->default('Private')->after('video_path');
            $table->timestamp('video_uploaded_at')->nullable()->after('video_visibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['video_path', 'video_visibility', 'video_uploaded_at']);
        });

        Schema::table('program_song_title', function (Blueprint $table) {
            $table->dropColumn(['video_path', 'video_visibility', 'video_uploaded_at']);
        });
    }
};
