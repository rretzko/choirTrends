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
            $table->foreignId('composer_id')->nullable()->constrained('artists')->nullOnDelete();
            $table->foreignId('arranger_id')->nullable()->constrained('artists')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('song_titles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('composer_id');
            $table->dropConstrainedForeignId('arranger_id');
        });
    }
};
