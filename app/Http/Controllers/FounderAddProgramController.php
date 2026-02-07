<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmProgramRequest;
use App\Http\Requests\StoreFounderProgramRequest;
use App\Jobs\ProcessProgram;
use App\Models\Artist;
use App\Models\Ensemble;
use App\Models\Program;
use App\Models\School;
use App\Models\SongTitle;
use App\Models\User;
use App\Services\ArtistNameParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FounderAddProgramController extends Controller
{
    public function index(Request $request)
    {
        $targetUserId = $request->session()->get('founder_target_user_id');
        $analysis = $targetUserId ? cache()->get("program_analysis_{$targetUserId}") : null;

        return view('founder.add-program', [
            'analysis' => $analysis,
            'users' => User::orderBy('alpha_name')->get(),
            'targetUserId' => $targetUserId,
        ]);
    }

    public function store(StoreFounderProgramRequest $request)
    {
        try {
            $targetUserId = (int) $request->input('target_user_id');

            // Store target user ID in session for subsequent requests
            $request->session()->put('founder_target_user_id', $targetUserId);

            $filePath = null;
            $uris = $request->input('program_uris');

            // Handle Vapor S3 direct upload (production on Vapor)
            if ($request->filled('vapor_file_key')) {
                $vaporKey = $request->input('vapor_file_key');
                $fileName = $request->input('vapor_file_name', 'uploaded_file');

                if (! Storage::exists($vaporKey)) {
                    throw new \Exception("Uploaded file not found in storage: {$vaporKey}");
                }

                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueName = Str::uuid().'.'.$extension;
                $permanentPath = 'concert-programs/'.$uniqueName;

                if (! Storage::copy($vaporKey, $permanentPath)) {
                    throw new \Exception("Failed to copy file from {$vaporKey} to {$permanentPath}");
                }

                if (! Storage::exists($permanentPath)) {
                    throw new \Exception("File copy verification failed: {$permanentPath} does not exist");
                }

                Storage::delete($vaporKey);
                $filePath = $permanentPath;
            }
            // Handle traditional file upload (local development)
            elseif ($request->hasFile('program_file')) {
                $file = $request->file('program_file');
                $filePath = $file->store('concert-programs');
            }

            // Clear any previous analysis for the target user
            cache()->forget("program_analysis_{$targetUserId}");

            // Set processing status
            cache()->put(
                "program_analysis_{$targetUserId}",
                ['status' => 'processing'],
                now()->addHours(2)
            );

            // Dispatch the job with the target user's ID
            ProcessProgram::dispatch(
                $targetUserId,
                $filePath ?? '',
                $uris
            );

            return redirect()
                ->route('founder.addProgram')
                ->with('success', 'Program is being processed for the selected user. This page will update automatically when complete.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function status(Request $request)
    {
        $targetUserId = $request->session()->get('founder_target_user_id');

        if (! $targetUserId) {
            return response()->json(['status' => 'not_found']);
        }

        $analysis = cache()->get("program_analysis_{$targetUserId}");

        return response()->json($analysis ?? ['status' => 'not_found']);
    }

    public function confirm(ConfirmProgramRequest $request)
    {
        $validated = $request->validated();
        $targetUserId = $request->session()->get('founder_target_user_id');

        if (! $targetUserId) {
            return redirect()
                ->route('founder.addProgram')
                ->withErrors(['error' => 'No target user selected. Please start over.']);
        }

        $targetUser = User::findOrFail($targetUserId);

        try {
            // Check if a program with the same user_id, event_name, and event_date already exists
            $exists = Program::where('user_id', $targetUser->id)
                ->where('event_name', $validated['event_name'])
                ->where('event_date', $validated['event_date'])
                ->exists();

            if ($exists) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'event_name' => 'A program for this event already exists for this user. Please use a different event name or date.',
                    ]);
            }

            // Find or create the school
            $school = School::firstOrCreate(['school_name' => $validated['school_name']]);

            // Associate the school with the target user (not the founder)
            $targetUser->schools()->syncWithoutDetaching([$school->id]);

            // Create the program attributed to the target user
            $program = Program::create([
                'user_id' => $targetUser->id,
                'event_name' => $validated['event_name'],
                'event_date' => $validated['event_date'],
                'school_id' => $school->id,
                'director_name' => $validated['director_name'],
            ]);

            // Get ensembles from the analysis cache and create them
            $analysis = cache()->get("program_analysis_{$targetUser->id}");
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

                    if (isset($ensembleData['songs']) && is_array($ensembleData['songs'])) {
                        foreach ($ensembleData['songs'] as $song) {
                            if (empty($song['title'])) {
                                continue;
                            }

                            $composerId = null;
                            $arrangerId = null;

                            if (! empty($song['composer'])) {
                                $composerData = $artistNameParser->parse($song['composer']);
                                $composer = Artist::firstOrCreate(
                                    ['artist_name' => $composerData['artist_name']],
                                    $composerData
                                );
                                $composerId = $composer->id;
                            }

                            if (! empty($song['arranger'])) {
                                $arrangerData = $artistNameParser->parse($song['arranger']);
                                $arranger = Artist::firstOrCreate(
                                    ['artist_name' => $arrangerData['artist_name']],
                                    $arrangerData
                                );
                                $arrangerId = $arranger->id;
                            }

                            $songTitle = SongTitle::firstOrCreate([
                                'song_title' => $song['title'],
                                'composer_id' => $composerId,
                                'arranger_id' => $arrangerId,
                            ]);

                            $songTitleAttachments[$songTitle->id] = ['ensemble_id' => $ensemble?->id];
                        }
                    }
                }
            }

            // Attach song titles to the program with ensemble information
            if (! empty($songTitleAttachments)) {
                $program->songTitles()->attach($songTitleAttachments);
            }

            // Clear the analysis cache and session
            cache()->forget("program_analysis_{$targetUser->id}");
            $request->session()->forget('founder_target_user_id');

            return redirect()
                ->route('founder.addProgram')
                ->with('success', "Program saved successfully for {$targetUser->name}!");
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors([
                        'event_name' => 'A program for this event already exists for this user. Please use a different event name or date.',
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
