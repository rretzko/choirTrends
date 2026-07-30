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
        Schema::create('stat_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('verified_users_count');
            $table->unsignedInteger('schools_count');
            $table->unsignedInteger('programs_count');
            $table->unsignedInteger('song_titles_count');
            $table->dateTime('captured_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stat_snapshots');
    }
};
