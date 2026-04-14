<?php

declare(strict_types=1);

use App\Livewire\Founder\Dashboard;
use App\Models\Program;
use App\Models\User;
use App\Models\UserLogin;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control ---

test('founder can access the dashboard page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.dashboard'))
        ->assertOk();
});

test('non-founder gets 403 on dashboard page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.dashboard'))
        ->assertForbidden();
});

test('guest is redirected from dashboard page', function () {
    $this->get(route('founder.dashboard'))
        ->assertRedirect(route('login'));
});

// --- Page Rendering ---

test('dashboard page renders with correct layout', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('Founder Dashboard')
        ->assertSee('Total Logins')
        ->assertSee('Unique Users')
        ->assertStatus(200);
});

test('dashboard shows correct totals', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $userA = User::factory()->withoutTwoFactor()->create();
    $userB = User::factory()->withoutTwoFactor()->create();

    UserLogin::factory()->create(['user_id' => $userA->id, 'counter' => 1]);
    UserLogin::factory()->create(['user_id' => $userA->id, 'counter' => 2]);
    UserLogin::factory()->create(['user_id' => $userB->id, 'counter' => 1]);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('3')  // total logins
        ->assertSee('2'); // unique users
});

test('dashboard shows recent logins with user details', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user = User::factory()->withoutTwoFactor()->create(['name' => 'Jane Smith']);

    UserLogin::factory()->create([
        'user_id' => $user->id,
        'os' => 'Windows',
        'browser' => 'Chrome',
        'device' => 'desktop',
        'viewport' => '1920x1080',
    ]);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('Jane Smith')
        ->assertSee('Windows')
        ->assertSee('Chrome')
        ->assertSee('1920x1080');
});

test('dashboard shows breakdown by OS', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    UserLogin::factory()->count(2)->create(['os' => 'Windows']);
    UserLogin::factory()->create(['os' => 'macOS']);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('By OS')
        ->assertSee('Windows')
        ->assertSee('macOS');
});

test('dashboard shows breakdown by browser', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    UserLogin::factory()->create(['browser' => 'Chrome']);
    UserLogin::factory()->create(['browser' => 'Firefox']);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('By Browser')
        ->assertSee('Chrome')
        ->assertSee('Firefox');
});

test('dashboard shows breakdown by device', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    UserLogin::factory()->create(['device' => 'desktop']);
    UserLogin::factory()->create(['device' => 'mobile']);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('By Device')
        ->assertSee('Desktop')
        ->assertSee('Mobile');
});

test('dashboard shows empty state when no logins exist', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('No login records yet.');
});

// --- Program Distribution ---

test('dashboard shows program distribution card with user count', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    User::factory()->withoutTwoFactor()->count(3)->create();

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->assertSee('Users by Program Count')
        ->assertSee('(n=4)');
});

test('dashboard distributes users into correct program count buckets', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    // User with 0 programs (founder + this user = 2 in the "0" bucket)
    User::factory()->withoutTwoFactor()->create();

    // User with 3 programs → "1–5" bucket
    $userWith3 = User::factory()->withoutTwoFactor()->create();
    Program::factory()->count(3)->create(['user_id' => $userWith3->id]);

    // User with 10 programs → "6–15" bucket
    $userWith10 = User::factory()->withoutTwoFactor()->create();
    Program::factory()->count(10)->create(['user_id' => $userWith10->id]);

    $component = Livewire::actingAs($founder)->test(Dashboard::class);

    $distribution = $component->get('programDistribution');

    expect($distribution[0]->label)->toBe('0')
        ->and($distribution[0]->count)->toBe(2)
        ->and($distribution[1]->label)->toBe('1–5')
        ->and($distribution[1]->count)->toBe(1)
        ->and($distribution[2]->label)->toBe('6–15')
        ->and($distribution[2]->count)->toBe(1)
        ->and($distribution[3]->count)->toBe(0)
        ->and($distribution[4]->count)->toBe(0)
        ->and($distribution[5]->count)->toBe(0);
});

test('dashboard modal shows user program details ordered by program count descending', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $userFew = User::factory()->withoutTwoFactor()->create(['name' => 'Alice Few']);
    Program::factory()->count(2)->create(['user_id' => $userFew->id]);

    $userMany = User::factory()->withoutTwoFactor()->create(['name' => 'Bob Many']);
    Program::factory()->count(8)->create(['user_id' => $userMany->id]);

    UserLogin::factory()->create(['user_id' => $userMany->id]);

    $component = Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->set('showProgramModal', true);

    $details = $component->get('userProgramDetails');

    expect($details->first()->name)->toBe('Bob Many')
        ->and($details->first()->programs_count)->toBe(8);

    $component->assertSee('Many, Bob')
        ->assertSee('Few, Alice');
});

test('dashboard modal shows "Never" for users who have not logged in', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    User::factory()->withoutTwoFactor()->create(['name' => 'No Login User']);

    Livewire::actingAs($founder)
        ->test(Dashboard::class)
        ->set('showProgramModal', true)
        ->assertSee('Never');
});
