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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AddProgramController extends Controller
{
    public function index(Request $request)
    {
        // Check if there's a completed analysis for this user
        $analysis = cache()->get("program_analysis_{$request->user()->id}");

        $userSchools = $request->user()->schools()->get(['schools.id', 'school_name', 'abbreviation']);

        $userSchoolsJson = $userSchools->map(fn ($s) => [
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
                ['status' => 'processing'],
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
        $analysis = cache()->get("program_analysis_{$request->user()->id}");

        return response()->json($analysis ?? ['status' => 'not_found']);
    }

    public function confirm(ConfirmProgramRequest $request)
    {
        $validated = $request->validated();

        try {
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

            // Find or create the school
            $school = School::firstOrCreate(['school_name' => $validated['school_name']]);

            // Associate the school with the user (if not already associated)
            $request->user()->schools()->syncWithoutDetaching([$school->id]);

            // Create the program first
            $program = Program::create([
                'user_id' => $request->user()->id,
                'event_name' => $validated['event_name'],
                'event_date' => $validated['event_date'],
                'school_id' => $school->id,
                'director_name' => $validated['director_name'],
            ]);

            // Get ensembles from the analysis cache and create them
            $analysis = cache()->get("program_analysis_{$request->user()->id}");
            $songTitleAttachments = [];

            if ($analysis && isset($analysis['data']['ensembles'])) {
                $artistNameParser = new ArtistNameParser;

                foreach ($analysis['data']['ensembles'] as $ensembleData) {
                    $ensemble = null;
                    if (! empty($ensembleData['name'])) {
                        $ensemble = Ensemble::firstOrCreate([
                            'school_id' => $school->id,
                            'ensemble_name' => $ensembleData['name'],
                        ]);
                    }

                    // Process songs for this ensemble
                    if (isset($ensembleData['songs']) && is_array($ensembleData['songs'])) {
                        foreach ($ensembleData['songs'] as $song) {
                            if (empty($song['title'])) {
                                continue;
                            }

                            $composerId = null;
                            $arrangerId = null;

                            // Create composer if it exists
                            if (! empty($song['composer'])) {
                                $composerData = $artistNameParser->parse($song['composer']);
                                $composer = Artist::firstOrCreate(
                                    ['artist_name' => $composerData['artist_name']],
                                    $composerData
                                );
                                $composerId = $composer->id;
                            }

                            // Create arranger if it exists
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

                            // Store song title with its ensemble for attachment
                            $songTitleAttachments[$songTitle->id] = ['ensemble_id' => $ensemble?->id];
                        }
                    }
                }
            }

            // Attach song titles to the program with ensemble information
            if (! empty($songTitleAttachments)) {
                $program->songTitles()->attach($songTitleAttachments);
            }

            // Clear the analysis cache
            cache()->forget("program_analysis_{$request->user()->id}");

            return redirect()
                ->route('dashboard')
                ->with('success', 'Program saved successfully!');
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle unique constraint violation
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'event_name' => 'A program for this event already exists. Please use a different event name or date.',
                    ]);
            }

            throw $e;
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save program: '.$e->getMessage()]);
        }
    }
}
