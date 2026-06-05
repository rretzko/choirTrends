<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate rows, keeping the most valuable record per (user_id, program_id)
        // pair: content wins over empty stubs; most recently updated breaks ties.
        // Child rows (honors, rosters, song_settings) are removed by DB cascade.
        $duplicates = DB::table('digital_programs')
            ->select('user_id', 'program_id')
            ->whereNotNull('program_id')
            ->groupBy('user_id', 'program_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $group) {
            $keepId = DB::table('digital_programs')
                ->where('user_id', $group->user_id)
                ->where('program_id', $group->program_id)
                ->orderByRaw('CASE WHEN welcome_message IS NOT NULL OR acknowledgments IS NOT NULL OR sponsor_text IS NOT NULL THEN 1 ELSE 0 END DESC')
                ->orderByDesc('updated_at')
                ->value('id');

            DB::table('digital_programs')
                ->where('user_id', $group->user_id)
                ->where('program_id', $group->program_id)
                ->where('id', '!=', $keepId)
                ->delete();
        }

        Schema::table('digital_programs', function (Blueprint $table) {
            $table->unique(['user_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::table('digital_programs', function (Blueprint $table) {
            $table->dropUnique(['digital_programs_user_id_program_id_unique']);
        });
    }
};
