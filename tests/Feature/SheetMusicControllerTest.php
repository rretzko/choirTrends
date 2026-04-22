<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserSongFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();
});

test('owner can view their sheet music file', function () {
    $user = User::factory()->create();

    $filePath = 'sheet-music/'.$user->id.'/1/owned.pdf';
    Storage::put($filePath, 'fake-pdf');

    $record = UserSongFile::factory()->create([
        'user_id' => $user->id,
        'file_path' => $filePath,
        'original_filename' => 'owned.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $this->actingAs($user)
        ->get(route('media.sheet-music.show', $record))
        ->assertOk();
});

test('non-owner receives 403 when requesting someone elses sheet music', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $filePath = 'sheet-music/'.$owner->id.'/1/private.pdf';
    Storage::put($filePath, 'fake-pdf');

    $record = UserSongFile::factory()->create([
        'user_id' => $owner->id,
        'file_path' => $filePath,
    ]);

    $this->actingAs($stranger)
        ->get(route('media.sheet-music.show', $record))
        ->assertForbidden();
});

test('guest is redirected to login', function () {
    $owner = User::factory()->create();
    $record = UserSongFile::factory()->create(['user_id' => $owner->id]);

    $this->get(route('media.sheet-music.show', $record))
        ->assertRedirect(route('login'));
});
