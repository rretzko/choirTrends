<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug', 8)->unique();
            $table->string('theme')->default('Formal');
            $table->boolean('is_published')->default(false);
            $table->text('welcome_message')->nullable();
            $table->text('acknowledgments')->nullable();
            $table->text('sponsor_text')->nullable();
            $table->unsignedInteger('intermission_after_ensemble')->nullable();
            $table->string('print_orientation')->default('Portrait');
            $table->boolean('student_names_acknowledged')->default(false);
            $table->boolean('lyrics_copyright_acknowledged')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_programs');
    }
};
