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
        Schema::create('artist_representations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('artist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('representation_category_id')->constrained()->cascadeOnDelete();
            $table->string('source_type');
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->text('source_excerpt')->nullable();
            $table->string('confidence');
            $table->string('status')->default('PendingReview');
            $table->foreignId('added_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['artist_id', 'representation_category_id', 'source_url'],
                'artist_representations_artist_category_source_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('artist_representations');
    }
};
