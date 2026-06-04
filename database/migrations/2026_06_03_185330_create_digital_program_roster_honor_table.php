<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_program_roster_honor', function (Blueprint $table) {
            $table->foreignId('digital_program_roster_id')->constrained('digital_program_rosters')->cascadeOnDelete();
            $table->foreignId('digital_program_honor_id')->constrained('digital_program_honors')->cascadeOnDelete();
            $table->primary(['digital_program_roster_id', 'digital_program_honor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_program_roster_honor');
    }
};
