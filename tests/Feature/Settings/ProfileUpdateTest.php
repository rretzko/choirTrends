<?php

use App\Livewire\Settings\Profile;
use App\Models\User;
use App\Models\UserPrivacy;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get('/settings/profile')->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test(Profile::class)
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('settings.delete-user-form')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});

test('user can update privacy settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('privacyName', true)
        ->set('privacySchool', true)
        ->set('privacyEnsembleName', true)
        ->call('updatePrivacySettings')
        ->assertHasNoErrors()
        ->assertDispatched('privacy-updated');

    $privacy = $user->fresh()->privacy;

    expect($privacy)->not->toBeNull();
    expect($privacy->name)->toBeTrue();
    expect($privacy->school)->toBeTrue();
    expect($privacy->ensemble_name)->toBeTrue();
});

test('privacy settings are loaded on mount', function () {
    $user = User::factory()->create();
    UserPrivacy::factory()->create([
        'user_id' => $user->id,
        'name' => true,
        'school' => false,
        'ensemble_name' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSet('privacyName', true)
        ->assertSet('privacySchool', false)
        ->assertSet('privacyEnsembleName', true);
});

test('user can reset privacy settings to share all information', function () {
    $user = User::factory()->create();
    UserPrivacy::factory()->create([
        'user_id' => $user->id,
        'name' => true,
        'school' => true,
        'ensemble_name' => true,
    ]);

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSet('privacyName', true)
        ->assertSet('privacySchool', true)
        ->assertSet('privacyEnsembleName', true)
        ->call('resetPrivacySettings')
        ->assertHasNoErrors()
        ->assertSet('privacyName', false)
        ->assertSet('privacySchool', false)
        ->assertSet('privacyEnsembleName', false)
        ->assertDispatched('privacy-updated');

    $privacy = $user->fresh()->privacy;

    expect($privacy->name)->toBeFalse();
    expect($privacy->school)->toBeFalse();
    expect($privacy->ensemble_name)->toBeFalse();
});

test('privacy settings default to false when not set', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->assertSet('privacyName', false)
        ->assertSet('privacySchool', false)
        ->assertSet('privacyEnsembleName', false);
});
