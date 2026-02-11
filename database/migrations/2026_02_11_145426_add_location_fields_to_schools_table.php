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
        Schema::table('schools', function (Blueprint $table) {
            $table->string('abbreviation', 20)->nullable()->after('school_name');
            $table->string('postal_code')->nullable()->after('abbreviation');
            $table->string('geo_state', 10)->nullable()->after('postal_code');
            $table->string('country', 2)->nullable()->after('geo_state');

            $table->dropUnique(['school_name']);
            $table->unique(['school_name', 'postal_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropUnique(['school_name', 'postal_code']);
            $table->unique('school_name');

            $table->dropColumn(['abbreviation', 'postal_code', 'geo_state', 'country']);
        });
    }
};
