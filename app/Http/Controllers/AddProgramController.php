<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmProgramRequest;
use App\Http\Requests\StoreProgramRequest;
use App\Jobs\ProcessProgram;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Services\ArtistNameParser;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class AddProgramController extends Controller
{
    /**
     * Job timeout (300s) plus a buffer for SQS/Lambda overhead before a
     * "processing" analysis is considered stalled and surfaced as failed.
     */
    private const PROCESSING_TIMEOUT_SECONDS = 360;

    public function index(Request $request)
    {
        $analysis = $this->resolveAnalysis($request->user()->id);

        $userSchools = $request->user()->schools()->get(['schools.id', 'school_name', 'abbreviation']);

        $userSchoolsJson = $userSchools->map(fn (School $s) => [
            'id' => $s->id,
            'school_name' => $s->school_name,
            'abbreviation' => $s->abbreviation ?? School::guessAbbreviation($s->school_name),
        ])->values();

        return view('add-program', [
            'analysis' => $analysis,
            'userSchools' => $userSchools,
            'userSchoolsJson' => $userSchoolsJson,
        ]);
    }

    public function store(StoreProgramRequest $request)
    {
        try {
            $filePath = null;
            $uris = $request->input('program_uris');

            // Handle Vapor S3 direct upload (production on Vapor)
            if ($request->filled('vapor_file_key')) {
                $vaporKey = $request->input('vapor_file_key');
                $fileName = $request->input('vapor_file_name', 'uploaded_file');

                // Verify the temporary file exists in S3
                if (! Storage::exists($vaporKey)) {
                    throw new \Exception("Uploaded file not found in storage: {$vaporKey}");
                }

                // Generate a unique filename with original extension
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueName = Str::uuid().'.'.$extension;

                // Move file from tmp/ to concert-programs/ in S3
                $permanentPath = 'concert-programs/'.$uniqueName;

                if (! Storage::copy($vaporKey, $permanentPath)) {
                    throw new \Exception("Failed to copy file from {$vaporKey} to {$permanentPath}");
                }

                // Verify the copy succeeded
                if (! Storage::exists($permanentPath)) {
                    throw new \Exception("File copy verification failed: {$permanentPath} does not exist");
                }

                // Delete the temporary file
                Storage::delete($vaporKey);

                $filePath = $permanentPath;
            }
            // Handle traditional file upload (local development)
            elseif ($request->hasFile('program_file')) {
                $file = $request->file('program_file');
                $filePath = $file->store('concert-programs');
            }

            // Clear any previous analysis
            cache()->forget("program_analysis_{$request->user()->id}");

            // Set processing status
            cache()->put(
                "program_analysis_{$request->user()->id}",
                ['status' => 'processing', 'started_at' => now()->toIso8601String()],
                now()->addHours(2)
            );

            // Dispatch the job
            ProcessProgram::dispatch(
                $request->user()->id,
                $filePath ?? '',
                $uris
            );

            return redirect()
                ->route('addProgram')
                ->with('success', 'Your program is being processed. This page will update automatically when complete.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function status(Request $request)
    {
        $analysis = $this->resolveAnalysis($request->user()->id);

        return response()->json($analysis ?? ['status' => 'not_found']);
    }

    public function reset(Request $request)
    {
        cache()->forget("program_analysis_{$request->user()->id}");

        return redirect()->route('addProgram');
    }

    /**
     * Fetch the cached analysis for a user, flipping a stalled "processing"
     * entry to "failed" if it has exceeded the expected processing window.
     *
     * @return array<string, mixed>|null
     */
    private function resolveAnalysis(int $userId): ?array
    {
        $cacheKey = "program_analysis_{$userId}";
        $analysis = cache()->get($cacheKey);

        if (($analysis['status'] ?? null) !== 'processing' || ! isset($analysis['started_at'])) {
            return $analysis;
        }

        $deadline = Carbon::parse($analysis['started_at'])->addSeconds(self::PROCESSING_TIMEOUT_SECONDS);

        if ($deadline->isFuture()) {
            return $analysis;
        }

        $analysis = [
            'status' => 'failed',
            'error' => 'Processing is taking longer than expected and may have failed. Please start over and try again.',
        ];

        cache()->put($cacheKey, $analysis, now()->addHours(2));

        return $analysis;
    }

    public function confirm(ConfirmProgramRequest $request)
    {
        $validated = $request->validated();

        // Check if a program with the same user_id, event_name, and event_date already exists
        $exists = Program::where('user_id', $request->user()->id)
            ->where('event_name', $validated['event_name'])
            ->where('event_date', $validated['event_date'])
            ->exists();

        if ($exists) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'event_name' => 'A program for this event already exists. Please use a different event name or date.',
                ]);
        }

        try {
            DB::transaction(function () use ($validated, $request) {
                // Find or create the school
                $school = School::firstOrCreate(['school_name' => $validated['school_name']]);

                // Associate the school with the user (if not already associated)
                $request->user()->schools()->syncWithoutDetaching([$school->id]);

                // Create the program
                $program = Program::create([
                    'user_id' => $request->user()->id,
                    'event_name' => $validated['event_name'],
                    'event_date' => $validated['event_date'],
                    'school_id' => $school->id,
                    'director_name' => $validated['director_name'],
                ]);

                // Process ensembles and songs from user-submitted form data
                $ensembles = $validated['ensembles'] ?? [];
                $songTitleAttachments = [];
                $ensembleSongOrder = [];
                $artistNameParser = new ArtistNameParser;

                foreach ($ensembles as $ensembleData) {
                    $ensemble = null;
                    if (! empty($ensembleData['name'])) {
                        $ensemble = Ensemble::firstOrCreate([
                            'school_id' => $school->id,
                            'ensemble_name' => $ensembleData['name'],
                        ]);
                    }

                    $ensembleKey = $ensemble->id ?? 0;
                    if (! isset($ensembleSongOrder[$ensembleKey])) {
                        $ensembleSongOrder[$ensembleKey] = 0;
                    }

                    $ensembleDirector = ! empty($ensembleData['director'])
                        ? $ensembleData['director']
                        : null;

                    // Process songs for this ensemble
                    if (isset($ensembleData['songs']) && is_array($ensembleData['songs'])) {
                        foreach ($ensembleData['songs'] as $song) {
                            if (empty($song['title'])) {
                                continue;
                            }

                            $composerId = null;
                            $arrangerId = null;

                            // Create composer if provided
                            if (! empty($song['composer'])) {
                                $composerData = $artistNameParser->parse($song['composer']);
                                $composer = Artist::firstOrCreate(
                                    ['artist_name' => $composerData['artist_name']],
                                    $composerData
                                );
                                $composerId = $composer->id;
                            }

                            // Create arranger if provided
                            if (! empty($song['arranger'])) {
                                $arrangerData = $artistNameParser->parse($song['arranger']);
                                $arranger = Artist::firstOrCreate(
                                    ['artist_name' => $arrangerData['artist_name']],
                                    $arrangerData
                                );
                                $arrangerId = $arranger->id;
                            }

                            // Create song title with composer and arranger (composite unique key)
                            $songTitle = SongTitle::firstOrCreate([
                                'song_title' => $song['title'],
                                'composer_id' => $composerId,
                                'arranger_id' => $arrangerId,
                            ]);

                            // Store song title with its ensemble and sort order for attachment
                            $ensembleSongOrder[$ensembleKey]++;
                            $songTitleAttachments[$songTitle->id] = [
                                'ensemble_id' => $ensemble?->id,
                                'sort_order' => $ensembleSongOrder[$ensembleKey],
                                'notes' => ! empty($song['notes'])
                                    ? Purifier::clean($song['notes'], 'program_notes')
                                    : null,
                                'ensemble_director' => $ensembleDirector,
                            ];
                        }
                    }
                }

                // Attach song titles to the program with ensemble information
                if (! empty($songTitleAttachments)) {
                    $program->songTitles()->attach($songTitleAttachments);
                }
            });

            // Clear the analysis cache after successful transaction
            cache()->forget("program_analysis_{$request->user()->id}");

            return redirect()
                ->route('dashboard')
                ->with('success', 'Program saved successfully!');
        } catch (QueryException $e) {
            if ($e->getCode() === '23000' && str_contains($e->getMessage(), 'programs')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'event_name' => 'A program for this event already exists. Please use a different event name or date.',
                    ]);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save program: '.$e->getMessage()]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save program: '.$e->getMessage()]);
        }
    }
}
