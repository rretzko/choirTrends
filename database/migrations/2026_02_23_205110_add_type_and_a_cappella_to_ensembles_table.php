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
        Schema::table('ensembles', function (Blueprint $table) {
            $table->string('type')->default('Unknown')->after('ensemble_name');
            $table->boolean('a_cappella')->default(false)->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ensembles', function (Blueprint $table) {
            $table->dropColumn(['type', 'a_cappella']);
        });
    }
};
