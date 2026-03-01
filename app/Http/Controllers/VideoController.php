<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\VideoVisibility;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class VideoController extends Controller
{
    public function programVideo(Request $request, Program $program): Response
    {
        abort_unless($program->video_path, 404);

        $isOwner = $program->user_id === $request->user()?->id;
        $isPublic = $program->video_visibility === VideoVisibility::Public;

        abort_unless($isOwner || $isPublic, 403);

        return $this->serveVideo($program->video_path);
    }

    public function songVideo(Request $request, Program $program, int $songTitle): Response
    {
        $pivot = $this->findSongPivot($program, $songTitle);

        abort_unless($pivot && $pivot->video_path, 404);

        $isOwner = $program->user_id === $request->user()?->id;
        $isPublic = $pivot->video_visibility === VideoVisibility::Public->value;

        abort_unless($isOwner || $isPublic, 403);

        return $this->serveVideo($pivot->video_path);
    }

    public function catalogProgramVideo(string $token, Program $program): Response
    {
        $user = $this->validateCatalogToken($token);

        abort_unless($program->user_id === $user->id, 404);
        abort_unless($program->video_path, 404);

        return $this->serveVideo($program->video_path);
    }

    public function catalogSongVideo(string $token, Program $program, int $songTitle): Response
    {
        $user = $this->validateCatalogToken($token);

        abort_unless($program->user_id === $user->id, 404);

        $pivot = $this->findSongPivot($program, $songTitle);

        abort_unless($pivot && $pivot->video_path, 404);

        return $this->serveVideo($pivot->video_path);
    }

    private function validateCatalogToken(string $token): User
    {
        $user = User::where('catalog_token', $token)
            ->whereNotNull('catalog_enabled_at')
            ->first();

        abort_unless($user, 404);

        return $user;
    }

    /**
     * @return object{video_path: string|null, video_visibility: string|null}|null
     */
    private function findSongPivot(Program $program, int $songTitleId): ?object
    {
        return $program->songTitles()
            ->wherePivot('song_title_id', $songTitleId)
            ->first()
            ?->pivot;
    }

    private function serveVideo(string $path): Response
    {
        $disk = Storage::disk();
        $driver = config('filesystems.disks.'.config('filesystems.default').'.driver');

        if ($driver === 's3') {
            return redirect((string) $disk->temporaryUrl($path, now()->addMinutes(30)));
        }

        $fullPath = $disk->path($path);

        abort_unless(file_exists($fullPath), 404);

        return response()->file($fullPath, [
            'Content-Type' => mime_content_type($fullPath) ?: 'video/mp4',
        ]);
    }
}
