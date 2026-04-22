<?php

declare(strict_types=1);

use App\Livewire\Founder\QuickTipForm;
use App\Livewire\Founder\QuickTips;
use App\Mail\QuickTipEmail;
use App\Models\QuickTip;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@example.com']);
});

// --- Access Control (Index) ---

test('founder can access the quick tips management page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.quickTips'))
        ->assertOk();
});

test('non-founder gets 403 on quick tips management page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.quickTips'))
        ->assertForbidden();
});

test('guest is redirected from quick tips management page', function () {
    $this->get(route('founder.quickTips'))
        ->assertRedirect(route('login'));
});

// --- Access Control (Form) ---

test('founder can access the create quick tip page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $this->actingAs($founder)
        ->get(route('founder.quickTips.create'))
        ->assertOk();
});

test('founder can access the edit quick tip page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->create();

    $this->actingAs($founder)
        ->get(route('founder.quickTips.edit', $tip))
        ->assertOk();
});

test('non-founder gets 403 on create quick tip page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user)
        ->get(route('founder.quickTips.create'))
        ->assertForbidden();
});

// --- Page Rendering ---

test('quick tips management page renders with correct layout', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(QuickTips::class)
        ->assertSee('Quick Tips')
        ->assertSee('Add New')
        ->assertOk();
});

test('shows empty state when no tips exist', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(QuickTips::class)
        ->assertSee('No quick tips yet')
        ->assertOk();
});

test('lists existing tips in sort order', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    QuickTip::factory()->create(['sort_order' => 2, 'header' => 'Second Tip']);
    QuickTip::factory()->create(['sort_order' => 1, 'header' => 'First Tip']);

    Livewire::actingAs($founder)
        ->test(QuickTips::class)
        ->assertSee('First Tip')
        ->assertSee('Second Tip')
        ->assertOk();
});

// --- CRUD Operations ---

test('founder can create a new quick tip', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class)
        ->set('header', 'Test Header')
        ->set('tip', '<p>Test tip content</p>')
        ->set('status', 'Pending')
        ->call('save');

    $this->assertDatabaseHas('quick_tips', [
        'header' => 'Test Header',
        'tip' => '<p>Test tip content</p>',
        'status' => 'Pending',
    ]);
});

test('founder can edit an existing quick tip', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->create(['header' => 'Original Header']);

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class, ['quickTip' => $tip])
        ->assertSet('header', 'Original Header')
        ->set('header', 'Updated Header')
        ->call('save');

    $this->assertDatabaseHas('quick_tips', [
        'id' => $tip->id,
        'header' => 'Updated Header',
    ]);
});

test('founder can delete a quick tip', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->create();

    Livewire::actingAs($founder)
        ->test(QuickTips::class)
        ->call('confirmDelete', $tip->id)
        ->call('delete');

    $this->assertDatabaseMissing('quick_tips', ['id' => $tip->id]);
});

test('founder can send a test email', function () {
    Mail::fake();
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->pending()->create(['header' => 'Test Tip']);

    Livewire::actingAs($founder)
        ->test(QuickTips::class)
        ->call('sendTestEmail', $tip->id);

    Mail::assertSent(QuickTipEmail::class, function ($mail) use ($founder, $tip) {
        return $mail->hasTo($founder->email) && $mail->quickTip->id === $tip->id;
    });
});

test('founder can view email preview', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->pending()->create(['header' => 'Preview Tip']);

    Livewire::actingAs($founder)
        ->test(QuickTips::class)
        ->call('openViewModal', $tip->id)
        ->assertSet('viewingTipId', $tip->id)
        ->assertSee('Preview Tip')
        ->assertSee('Email Preview');
});

// --- Edit Page Population ---

test('edit page populates all fields from the tip', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->pending()->withIntroduction()->withFooter()->withCallToAction()->create([
        'sort_order' => 7,
        'header' => 'Test Header',
        'tip' => '<p>Test tip content</p>',
    ]);

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class, ['quickTip' => $tip])
        ->assertSet('sortOrder', 7)
        ->assertSet('header', 'Test Header')
        ->assertSet('introduction', $tip->introduction)
        ->assertSet('tip', '<p>Test tip content</p>')
        ->assertSet('footer', $tip->footer)
        ->assertSet('callToAction', $tip->call_to_action)
        ->assertSet('status', 'Pending');
});

test('edit page populates tip and introduction even when they contain HTML', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->pending()->create([
        'introduction' => '<p>Intro with <strong>bold</strong> text</p>',
        'tip' => '<p>Tip with <ul><li>list items</li></ul></p>',
    ]);

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class, ['quickTip' => $tip])
        ->assertSet('introduction', '<p>Intro with <strong>bold</strong> text</p>')
        ->assertSet('tip', '<p>Tip with <ul><li>list items</li></ul></p>');
});

test('edit page populates empty strings for null introduction, footer, and call to action', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->pending()->create([
        'introduction' => null,
        'footer' => null,
        'call_to_action' => null,
    ]);

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class, ['quickTip' => $tip])
        ->assertSet('introduction', '')
        ->assertSet('footer', '')
        ->assertSet('callToAction', '');
});

test('saving edited tip persists all fields including introduction and tip', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->pending()->create([
        'tip' => '<p>Original</p>',
        'introduction' => '<p>Original intro</p>',
    ]);

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class, ['quickTip' => $tip])
        ->set('introduction', '<p>Updated intro</p>')
        ->set('tip', '<p>Updated tip</p>')
        ->call('save');

    $this->assertDatabaseHas('quick_tips', [
        'id' => $tip->id,
        'introduction' => '<p>Updated intro</p>',
        'tip' => '<p>Updated tip</p>',
    ]);
});

// --- Validation ---

test('header is required when creating a tip', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class)
        ->set('header', '')
        ->set('tip', '<p>Content</p>')
        ->call('save')
        ->assertHasErrors(['header' => 'required']);
});

test('tip content is required when creating a tip', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class)
        ->set('header', 'Valid Header')
        ->set('tip', '')
        ->call('save')
        ->assertHasErrors(['tip' => 'required']);
});

// --- Default Footer/CTA Pre-population ---

test('create page pre-populates footer and call to action from config', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class)
        ->assertSet('footer', config('quicktip.default_footer'))
        ->assertSet('callToAction', config('quicktip.default_call_to_action'));
});

test('create page auto-calculates next sort order', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    QuickTip::factory()->create(['sort_order' => 5]);

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class)
        ->assertSet('sortOrder', 6);
});

// --- Save redirects back to index ---

test('saving a new tip redirects to the index page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class)
        ->set('header', 'Redirect Test')
        ->set('tip', '<p>Content</p>')
        ->call('save')
        ->assertRedirect(route('founder.quickTips'));
});

test('saving an edited tip redirects to the index page', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $tip = QuickTip::factory()->create();

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class, ['quickTip' => $tip])
        ->set('header', 'Updated')
        ->call('save')
        ->assertRedirect(route('founder.quickTips'));
});

// --- Next Mailing date reference ---

test('form shows next mailing date as 21 days after the most recent emailed_at', function () {
    $founder = User::factory()->withoutTwoFactor()->founder()->create();
    $user = User::factory()->withoutTwoFactor()->create();
    $tip = QuickTip::factory()->create();

    $lastEmailed = Carbon::parse('2026-04-01 12:00:00');
    DB::table('quick_tip_user')->insert([
        'quick_tip_id' => $tip->id,
        'user_id' => $user->id,
        'emailed_at' => $lastEmailed,
    ]);

    $expected = $lastEmailed->copy()->addDays(21)->format('F j, Y');

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class)
        ->assertSee('Next Mailing')
        ->assertSee($expected);
});

test('form falls back to now + 21 days when no prior mailings exist', function () {
    Carbon::setTestNow('2026-04-22 09:00:00');
    $founder = User::factory()->withoutTwoFactor()->founder()->create();

    $expected = Carbon::now()->addDays(21)->format('F j, Y');

    Livewire::actingAs($founder)
        ->test(QuickTipForm::class)
        ->assertSee('Next Mailing')
        ->assertSee($expected);

    Carbon::setTestNow();
});
