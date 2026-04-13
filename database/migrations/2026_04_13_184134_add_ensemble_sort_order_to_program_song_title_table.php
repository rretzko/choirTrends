<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('program_song_title', function (Blueprint $table) {
            $table->unsignedSmallInteger('ensemble_sort_order')->default(0)->after('sort_order');
        });

        // Seed ensemble_sort_order based on the lowest sort_order per ensemble within each program
        $groups = DB::table('program_song_title')
            ->select('program_id', 'ensemble_id')
            ->groupBy('program_id', 'ensemble_id')
            ->orderBy('program_id')
            ->orderByRaw('MIN(sort_order)')
            ->get();

        $counters = [];

        foreach ($groups as $group) {
            $key = (string) $group->program_id;
            $counters[$key] = ($counters[$key] ?? 0) + 1;

            DB::table('program_song_title')
                ->where('program_id', $group->program_id)
                ->where(function ($q) use ($group) {
                    if ($group->ensemble_id === null) {
                        $q->whereNull('ensemble_id');
                    } else {
                        $q->where('ensemble_id', $group->ensemble_id);
                    }
                })
                ->update(['ensemble_sort_order' => $counters[$key]]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_song_title', function (Blueprint $table) {
            $table->dropColumn('ensemble_sort_order');
        });
    }
};
