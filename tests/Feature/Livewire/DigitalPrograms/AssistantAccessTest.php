<?php

use App\Enums\UserRole;
use App\Livewire\DigitalPrograms\AssistantAccess;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

test('director can create an assistant account', function () {
    $director = User::factory()->create();

    $this->actingAs($director);

    Livewire::test(AssistantAccess::class)
        ->call('createAssistant');

    $assistant = $director->fresh()->assistant;

    expect($assistant)->not->toBeNull();
    expect($assistant->role)->toBe(UserRole::Assistant);
    expect($assistant->parent_user_id)->toBe($director->id);
    expect($assistant->email_verified_at)->not->toBeNull();
});

test('creating an assistant twice does not create a duplicate', function () {
    $director = User::factory()->create();

    $this->actingAs($director);

    $component = Livewire::test(AssistantAccess::class);
    $component->call('createAssistant');
    $component->call('createAssistant');

    expect(User::where('parent_user_id', $director->id)->count())->toBe(1);
});

test('generated assistant credentials can authenticate', function () {
    $director = User::factory()->create();

    $this->actingAs($director);

    $component = Livewire::test(AssistantAccess::class)
        ->call('createAssistant');

    $assistant = $director->fresh()->assistant;
    $password = $component->get('generatedPassword');

    expect($password)->not->toBeNull();
    expect(Auth::attempt(['email' => $assistant->email, 'password' => $password]))->toBeTrue();
});

test('director can reset the assistant password', function () {
    $director = User::factory()->create();

    $this->actingAs($director);

    Livewire::test(AssistantAccess::class)->call('createAssistant');

    $assistant = $director->fresh()->assistant;
    $oldPasswordHash = $assistant->password;

    $component = Livewire::test(AssistantAccess::class)->call('resetPassword');

    $newPassword = $component->get('generatedPassword');

    expect($assistant->fresh()->password)->not->toBe($oldPasswordHash);
    expect(Auth::attempt(['email' => $assistant->email, 'password' => $newPassword]))->toBeTrue();
});

test('director can remove assistant access', function () {
    $director = User::factory()->create();

    $this->actingAs($director);

    Livewire::test(AssistantAccess::class)->call('createAssistant');

    $assistantId = $director->fresh()->assistant->id;

    Livewire::test(AssistantAccess::class)->call('removeAssistant');

    expect(User::find($assistantId))->toBeNull();
    expect($director->fresh()->assistant)->toBeNull();
});

test('assistant accounts cannot manage assistant access', function () {
    $director = User::factory()->create();
    $assistant = User::factory()->assistant($director)->create();

    $this->actingAs($assistant);

    Livewire::test(AssistantAccess::class)
        ->call('createAssistant')
        ->assertForbidden();
});
