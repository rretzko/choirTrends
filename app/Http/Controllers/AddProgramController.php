<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProgramRequest;
use App\Jobs\ProcessProgram;
use Illuminate\Http\Request;

class AddProgramController extends Controller
{
    public function index(Request $request)
    {
        // Check if there's a completed analysis for this user
        $analysis = cache()->get("program_analysis_{$request->user()->id}");

        return view('add-program', [
            'analysis' => $analysis,
        ]);
    }

    public function store(StoreProgramRequest $request)
    {
        try {
            $filePath = null;
            $uris = $request->input('program_uris');

            // Store file temporarily if uploaded
            if ($request->hasFile('program_file')) {
                $file = $request->file('program_file');
                $filePath = $file->store('temp_programs');
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
}
