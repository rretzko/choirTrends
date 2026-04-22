<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserSongFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SheetMusicController extends Controller
{
    public function show(Request $request, UserSongFile $file): Response
    {
        abort_unless($file->user_id === $request->user()?->id, 403);

        $disk = Storage::disk();
        $driver = config('filesystems.disks.'.config('filesystems.default').'.driver');

        if ($driver === 's3') {
            return redirect((string) $disk->temporaryUrl($file->file_path, now()->addMinutes(30)));
        }

        $fullPath = $disk->path($file->file_path);

        abort_unless(file_exists($fullPath), 404);

        return response()->file($fullPath, [
            'Content-Type' => $file->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_filename).'"',
        ]);
    }
}
