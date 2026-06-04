<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_program_honors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ensemble_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 100);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_program_honors');
    }
};
