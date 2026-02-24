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
            $table->unsignedSmallInteger('sort_order')->default(0)->after('ensemble_id');
        });

        // Seed sort_order based on current row order within each (program_id, ensemble_id) group
        $rows = DB::table('program_song_title')
            ->orderBy('program_id')
            ->orderBy('ensemble_id')
            ->orderBy('song_title_id')
            ->get();

        $counters = [];

        foreach ($rows as $row) {
            $key = $row->program_id.'-'.$row->ensemble_id;
            $counters[$key] = ($counters[$key] ?? 0) + 1;

            DB::table('program_song_title')
                ->where('program_id', $row->program_id)
                ->where('song_title_id', $row->song_title_id)
                ->update(['sort_order' => $counters[$key]]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_song_title', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
