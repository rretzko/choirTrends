<?php

use App\Enums\SchoolType;
use App\Livewire\Settings\ProfileSchools;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('profile schools component renders on profile page', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/settings/profile')
        ->assertOk()
        ->assertSeeLivewire(ProfileSchools::class);
});

test('empty state message is displayed when user has no schools', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ProfileSchools::class)
        ->assertSee("You haven't added any schools/orgs yet");
});

test('user schools are displayed', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Lincoln High School']);
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(ProfileSchools::class)
        ->assertSee('Lincoln High School');
});

test('user can add a new school', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Http::fake();

    Livewire::test(ProfileSchools::class)
        ->set('schoolName', 'Central Valley High School')
        ->set('abbreviation', 'CVHS')
        ->set('postalCode', '90210')
        ->set('geoState', 'CA')
        ->set('country', 'US')
        ->call('saveSchool')
        ->assertHasNoErrors();

    expect($user->fresh()->schools)->toHaveCount(1);
    expect($user->fresh()->schools->first()->school_name)->toBe('Central Valley High School');
});

test('school name is required when saving', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ProfileSchools::class)
        ->set('schoolName', '')
        ->call('saveSchool')
        ->assertHasErrors(['schoolName' => 'required']);
});

test('existing school is reused when name and postal code match', function () {
    $user = User::factory()->create();
    $existing = School::factory()->create([
        'school_name' => 'Lincoln High School',
        'postal_code' => '90210',
    ]);

    $this->actingAs($user);

    Livewire::test(ProfileSchools::class)
        ->set('schoolName', 'Lincoln High School')
        ->set('postalCode', '90210')
        ->call('saveSchool')
        ->assertHasNoErrors();

    expect($user->fresh()->schools->first()->id)->toBe($existing->id);
    expect(School::where('school_name', 'Lincoln High School')->count())->toBe(1);
});

test('duplicate attach is prevented', function () {
    $user = User::factory()->create();
    $school = School::factory()->create([
        'school_name' => 'Lincoln High School',
        'postal_code' => '90210',
    ]);
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(ProfileSchools::class)
        ->set('schoolName', 'Lincoln High School')
        ->set('postalCode', '90210')
        ->call('saveSchool')
        ->assertHasNoErrors();

    expect($user->fresh()->schools)->toHaveCount(1);
});

test('user can edit their school', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Old Name']);
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(ProfileSchools::class)
        ->call('openEditModal', $school->id)
        ->assertSet('isEditing', true)
        ->assertSet('schoolName', 'Old Name')
        ->set('schoolName', 'New Name')
        ->call('saveSchool')
        ->assertHasNoErrors();

    expect($school->fresh()->school_name)->toBe('New Name');
});

test('user cannot edit a school they are not attached to', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_name' => 'Not My School']);

    $this->actingAs($user);

    Livewire::test(ProfileSchools::class)
        ->call('openEditModal', $school->id)
        ->assertSet('isEditing', false)
        ->assertSet('editingSchoolId', null);
});

test('user can remove a school from their profile', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(ProfileSchools::class)
        ->call('removeSchool', $school->id);

    expect($user->fresh()->schools)->toHaveCount(0);
    expect(School::find($school->id))->not->toBeNull();
});

test('postal code lookup populates state and country', function () {
    Http::fake([
        'api.zippopotam.us/us/90210' => Http::response([
            'country abbreviation' => 'US',
            'places' => [
                ['state abbreviation' => 'CA'],
            ],
        ]),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test(ProfileSchools::class)
        ->set('postalCode', '90210')
        ->assertSet('geoState', 'CA')
        ->assertSet('country', 'US');
});

test('failed postal code lookup clears state and country', function () {
    Http::fake([
        'api.zippopotam.us/us/00000' => Http::response([], 404),
    ]);

    $this->actingAs(User::factory()->create());

    Livewire::test(ProfileSchools::class)
        ->set('postalCode', '00000')
        ->assertSet('geoState', '')
        ->assertSet('country', '');
});

test('short postal code clears state and country', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ProfileSchools::class)
        ->set('postalCode', '90')
        ->assertSet('geoState', '')
        ->assertSet('country', '');
});

test('school name auto-generates abbreviation', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ProfileSchools::class)
        ->set('schoolName', 'Central Valley High School')
        ->assertSet('abbreviation', 'CVHS');
});

test('school type defaults to high school when adding', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(ProfileSchools::class)
        ->call('openAddModal')
        ->assertSet('schoolType', SchoolType::HighSchool->value);
});

test('user can add a school with a non-school type', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Http::fake();

    Livewire::test(ProfileSchools::class)
        ->set('schoolName', 'First Baptist Choir')
        ->set('schoolType', SchoolType::ChurchChoir->value)
        ->call('saveSchool')
        ->assertHasNoErrors();

    expect($user->fresh()->schools->first()->school_type)->toBe(SchoolType::ChurchChoir);
});

test('editing a school loads its type', function () {
    $user = User::factory()->create();
    $school = School::factory()->create(['school_type' => SchoolType::CommunityChoir]);
    $user->schools()->attach($school);

    $this->actingAs($user);

    Livewire::test(ProfileSchools::class)
        ->call('openEditModal', $school->id)
        ->assertSet('schoolType', SchoolType::CommunityChoir->value);
});
