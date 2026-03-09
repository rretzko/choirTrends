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
        Schema::create('quick_tips', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->unique();
            $table->string('header');
            $table->text('introduction')->nullable();
            $table->text('tip');
            $table->text('footer')->nullable();
            $table->text('call_to_action')->nullable();
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_tips');
    }
};
