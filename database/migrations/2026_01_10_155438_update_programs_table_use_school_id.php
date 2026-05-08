<?php

use App\Models\Program;
use App\Models\School;
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
        // Add school_id column as nullable first (only if it doesn't exist)
        if (! Schema::hasColumn('programs', 'school_id')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable()->after('user_id');
            });
        }

        // Migrate existing data: create schools from existing school_name and update programs
        if (Schema::hasColumn('programs', 'school_name')) {
            $programs = Program::all();
            foreach ($programs as $program) {
                if ($program->school_name) {
                    $school = School::firstOrCreate(['school_name' => $program->school_name]);
                    $program->school_id = $school->id;
                    $program->save();
                }
            }
        }

        // Drop school_name column and add foreign key constraint
        Schema::table('programs', function (Blueprint $table) {
            if (Schema::hasColumn('programs', 'school_name')) {
                $table->dropColumn('school_name');
            }
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
        });

        // Add foreign key constraint if it doesn't exist
        // Check varies by database driver
        $foreignKeyExists = false;

        try {
            if (DB::getDriverName() === 'mysql') {
                $foreignKeyExists = ! empty(DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.TABLE_CONSTRAINTS
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'programs'
                    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
                    AND CONSTRAINT_NAME = 'programs_school_id_foreign'
                "));
            } else {
                // For SQLite and other databases, try adding the constraint
                // If it already exists, it will fail gracefully in the try-catch below
                $foreignKeyExists = false;
            }
        } catch (Exception $e) {
            $foreignKeyExists = false;
        }

        if (! $foreignKeyExists) {
            try {
                Schema::table('programs', function (Blueprint $table) {
                    $table->foreign('school_id')->references('id')->on('schools')->cascadeOnDelete();
                });
            } catch (Exception $e) {
                // Foreign key already exists, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add school_name column back
        Schema::table('programs', function (Blueprint $table) {
            $table->string('school_name')->nullable()->after('user_id');
        });

        // Migrate data back: copy school names from schools table
        $programs = Program::with('school')->get();
        foreach ($programs as $program) {
            if ($program->school) {
                $program->school_name = $program->school->school_name;
                $program->save();
            }
        }

        // Drop foreign key and school_id column
        Schema::table('programs', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn('school_id');
            $table->string('school_name')->nullable(false)->change();
        });
    }
};
