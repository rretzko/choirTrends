<?php

declare(strict_types=1);

use App\Livewire\Founder\Newsletter;
use App\Mail\Newsletter as NewsletterMailable;
use App\Models\Newsletter as NewsletterModel;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    config(['app.founder' => 'founder@test.com']);
    Mail::fake();

    $this->founder = User::factory()->withoutTwoFactor()->create(['email' => 'founder@test.com']);
});

test('non-founder cannot access newsletter page', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $this->actingAs($user);

    $this->get(route('founder.newsletter'))
        ->assertForbidden();
});

test('founder can access newsletter page', function () {
    $this->actingAs($this->founder);

    $this->get(route('founder.newsletter'))
        ->assertOk()
        ->assertSeeLivewire(Newsletter::class);
});

test('guest is redirected to login', function () {
    $this->get(route('founder.newsletter'))
        ->assertRedirect(route('login'));
});

test('component loads latest draft on mount', function () {
    $this->actingAs($this->founder);

    $draft = NewsletterModel::factory()->create(['subject' => 'My Draft']);

    Livewire::test(Newsletter::class)
        ->assertSet('newsletterId', $draft->id)
        ->assertSet('subject', 'My Draft')
        ->assertStatus(200);
});

test('component sets default cta url when no draft exists', function () {
    $this->actingAs($this->founder);

    Livewire::test(Newsletter::class)
        ->assertSet('newsletterId', null)
        ->assertSet('ctaUrl', route('addProgram'))
        ->assertStatus(200);
});

test('save creates a new newsletter', function () {
    $this->actingAs($this->founder);

    Livewire::test(Newsletter::class)
        ->set('subject', 'Test Subject')
        ->set('headline', 'Test Headline')
        ->set('intro', 'Test intro paragraph.')
        ->set('sections', [['title' => 'Section 1', 'body' => '<p>Body</p>']])
        ->set('ctaText', 'Click Here')
        ->set('ctaUrl', 'https://example.com')
        ->set('closing', 'Thanks!')
        ->call('save')
        ->assertHasNoErrors();

    expect(NewsletterModel::query()->where('subject', 'Test Subject')->exists())->toBeTrue();
});

test('save validates required fields', function () {
    $this->actingAs($this->founder);

    Livewire::test(Newsletter::class)
        ->set('subject', '')
        ->set('headline', '')
        ->set('intro', '')
        ->set('sections', [])
        ->set('ctaText', '')
        ->set('ctaUrl', '')
        ->set('closing', '')
        ->call('save')
        ->assertHasErrors(['subject', 'headline', 'intro', 'sections', 'ctaText', 'ctaUrl', 'closing']);
});

test('save prevents editing a sent newsletter', function () {
    $this->actingAs($this->founder);

    $sent = NewsletterModel::factory()->sent()->create();

    Livewire::test(Newsletter::class, ['newsletter' => $sent->id])
        ->set('subject', 'Changed Subject')
        ->call('save');

    expect($sent->fresh()->subject)->not->toBe('Changed Subject');
});

test('sendPreview sends email only to founder', function () {
    $this->actingAs($this->founder);

    User::factory()->withoutTwoFactor()->count(3)->create();
    $draft = NewsletterModel::factory()->create();

    Livewire::test(Newsletter::class, ['newsletter' => $draft->id])
        ->call('sendPreview')
        ->assertHasNoErrors();

    Mail::assertSent(NewsletterMailable::class, 1);
    Mail::assertSent(NewsletterMailable::class, function (NewsletterMailable $mail) {
        return $mail->user->id === $this->founder->id;
    });

    expect($draft->fresh()->sent_at)->toBeNull();
});

test('sendToAll sends to all verified users and marks newsletter as sent', function () {
    $this->actingAs($this->founder);

    $verified = User::factory()->withoutTwoFactor()->count(3)->create();
    User::factory()->withoutTwoFactor()->create(['email_verified_at' => null]);

    $draft = NewsletterModel::factory()->create();

    Livewire::test(Newsletter::class, ['newsletter' => $draft->id])
        ->call('sendToAll')
        ->assertHasNoErrors();

    // 3 verified + the founder = 4 verified users
    Mail::assertSent(NewsletterMailable::class, 4);

    expect($draft->fresh())
        ->sent_at->not->toBeNull()
        ->recipient_count->toBe(4);
});

test('sendToAll prevents resending a sent newsletter', function () {
    $this->actingAs($this->founder);

    $sent = NewsletterModel::factory()->sent()->create();

    Livewire::test(Newsletter::class, ['newsletter' => $sent->id])
        ->call('sendToAll');

    Mail::assertNotSent(NewsletterMailable::class);
});

test('createNew resets form fields', function () {
    $this->actingAs($this->founder);

    $draft = NewsletterModel::factory()->create(['subject' => 'Existing Draft']);

    Livewire::test(Newsletter::class, ['newsletter' => $draft->id])
        ->assertSet('subject', 'Existing Draft')
        ->call('createNew')
        ->assertSet('newsletterId', null)
        ->assertSet('subject', '')
        ->assertSet('headline', '')
        ->assertSet('sections', [])
        ->assertSet('ctaUrl', route('addProgram'));
});

test('loadExisting loads a newsletter into the form', function () {
    $this->actingAs($this->founder);

    $newsletter = NewsletterModel::factory()->create([
        'subject' => 'Load Me',
        'headline' => 'My Headline',
    ]);

    Livewire::test(Newsletter::class)
        ->call('loadExisting', $newsletter->id)
        ->assertSet('newsletterId', $newsletter->id)
        ->assertSet('subject', 'Load Me')
        ->assertSet('headline', 'My Headline');
});

test('addSection appends a new section', function () {
    $this->actingAs($this->founder);

    Livewire::test(Newsletter::class)
        ->set('newSectionTitle', 'New Section')
        ->set('newSectionBody', '<p>Content</p>')
        ->call('addSection')
        ->assertSet('sections', [['title' => 'New Section', 'body' => '<p>Content</p>']])
        ->assertSet('newSectionTitle', '')
        ->assertSet('newSectionBody', '');
});

test('addSection validates required fields', function () {
    $this->actingAs($this->founder);

    Livewire::test(Newsletter::class)
        ->set('newSectionTitle', '')
        ->set('newSectionBody', '')
        ->call('addSection')
        ->assertHasErrors(['newSectionTitle', 'newSectionBody']);
});

test('editSection loads section data for editing', function () {
    $this->actingAs($this->founder);

    $draft = NewsletterModel::factory()->create([
        'sections' => [
            ['title' => 'First', 'body' => '<p>First body</p>'],
            ['title' => 'Second', 'body' => '<p>Second body</p>'],
        ],
    ]);

    Livewire::test(Newsletter::class, ['newsletter' => $draft->id])
        ->call('editSection', 1)
        ->assertSet('editingSectionIndex', 1)
        ->assertSet('newSectionTitle', 'Second')
        ->assertSet('newSectionBody', '<p>Second body</p>');
});

test('addSection updates existing section when editing', function () {
    $this->actingAs($this->founder);

    $draft = NewsletterModel::factory()->create([
        'sections' => [['title' => 'Original', 'body' => '<p>Original</p>']],
    ]);

    Livewire::test(Newsletter::class, ['newsletter' => $draft->id])
        ->call('editSection', 0)
        ->set('newSectionTitle', 'Updated')
        ->set('newSectionBody', '<p>Updated</p>')
        ->call('addSection')
        ->assertSet('sections', [['title' => 'Updated', 'body' => '<p>Updated</p>']])
        ->assertSet('editingSectionIndex', null);
});

test('removeSection removes a section by index', function () {
    $this->actingAs($this->founder);

    $draft = NewsletterModel::factory()->create([
        'sections' => [
            ['title' => 'Keep', 'body' => '<p>Keep</p>'],
            ['title' => 'Remove', 'body' => '<p>Remove</p>'],
        ],
    ]);

    Livewire::test(Newsletter::class, ['newsletter' => $draft->id])
        ->call('removeSection', 1)
        ->assertSet('sections', [['title' => 'Keep', 'body' => '<p>Keep</p>']]);
});

test('recipient count reflects verified users', function () {
    $this->actingAs($this->founder);

    User::factory()->withoutTwoFactor()->count(5)->create();
    User::factory()->withoutTwoFactor()->count(2)->create(['email_verified_at' => null]);

    Livewire::test(Newsletter::class)
        ->assertSet('recipientCount', 6); // 5 + the founder
});
