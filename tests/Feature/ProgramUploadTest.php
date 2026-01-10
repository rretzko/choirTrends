<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('authenticated user can access add program page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->get(route('addProgram'));

    $response->assertSuccessful();
    $response->assertViewIs('add-program');
});

test('guest cannot access add program page', function () {
    $response = $this->get(route('addProgram'));

    $response->assertRedirect(route('login'));
});

test('user can upload a program file', function () {
    Queue::fake();

    $user = User::factory()->withoutTwoFactor()->create();
    $file = UploadedFile::fake()->create('program.pdf', 1000, 'application/pdf');

    $response = $this->actingAs($user)->post(route('addProgram.store'), [
        'program_file' => $file,
    ]);

    $response->assertRedirect(route('addProgram'));
    $response->assertSessionHas('success');

    Queue::assertPushed(\App\Jobs\ProcessProgram::class, function ($job) use ($user) {
        return $job->userId === $user->id
            && $job->filePath !== ''
            && $job->uris === null;
    });
});

test('user can submit program URLs', function () {
    Queue::fake();

    $user = User::factory()->withoutTwoFactor()->create();
    $urls = "https://example.com/program1\nhttps://example.com/program2";

    $response = $this->actingAs($user)->post(route('addProgram.store'), [
        'program_uris' => $urls,
    ]);

    $response->assertRedirect(route('addProgram'));
    $response->assertSessionHas('success');

    Queue::assertPushed(\App\Jobs\ProcessProgram::class, function ($job) use ($user, $urls) {
        return $job->userId === $user->id
            && $job->filePath === ''
            && $job->uris === $urls;
    });
});

test('processing status is set in cache when program is uploaded', function () {
    Queue::fake();

    $user = User::factory()->withoutTwoFactor()->create();
    $file = UploadedFile::fake()->create('program.pdf', 1000, 'application/pdf');

    $this->actingAs($user)->post(route('addProgram.store'), [
        'program_file' => $file,
    ]);

    $analysis = cache()->get("program_analysis_{$user->id}");

    expect($analysis)->toBeArray()
        ->and($analysis['status'])->toBe('processing');
});

test('status endpoint returns current processing status', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    // Set processing status in cache
    cache()->put(
        "program_analysis_{$user->id}",
        ['status' => 'processing'],
        now()->addHours(2)
    );

    $response = $this->actingAs($user)->get(route('addProgram.status'));

    $response->assertSuccessful();
    $response->assertJson(['status' => 'processing']);
});

test('status endpoint returns not found when no analysis exists', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->get(route('addProgram.status'));

    $response->assertSuccessful();
    $response->assertJson(['status' => 'not_found']);
});

test('file is stored temporarily when uploaded', function () {
    Queue::fake();

    $user = User::factory()->withoutTwoFactor()->create();
    $file = UploadedFile::fake()->create('program.pdf', 1000, 'application/pdf');

    $this->actingAs($user)->post(route('addProgram.store'), [
        'program_file' => $file,
    ]);

    Storage::assertExists('temp_programs/'.$file->hashName());
});

test('validation fails when neither file nor URIs are provided', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->actingAs($user)->post(route('addProgram.store'), []);

    $response->assertSessionHasErrors();
});
