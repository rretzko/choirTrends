# Onboarding System: Welcome Modal + Setup Checklist

## Context

New users land on the dashboard with no guidance on what to do next. The app requires users to add schools, upload programs (within 14 days for compliance), and set privacy preferences — but nothing currently walks them through this. This plan adds a one-time welcome modal and a persistent setup checklist card on the dashboard.

## Overview

- **Welcome Modal**: One-time `flux:modal` that auto-opens for new users, explains the app, previews first steps. Dismissed via "Let's go!" button, tracked by `welcomed_at` timestamp.
- **Setup Checklist**: Card above the stat grid showing 3 actionable items with live completion status. Auto-hides when all complete. Can be dismissed early via `onboarding_dismissed_at` timestamp.

---

## Step 1: Migration

Create migration to add two nullable timestamp columns to `users`:

```
php artisan make:migration add_onboarding_columns_to_users_table --no-interaction
```

Columns: `welcomed_at` (nullable timestamp, after `remember_token`), `onboarding_dismissed_at` (nullable timestamp, after `welcomed_at`).

Run `php artisan migrate`.

## Step 2: Update User Model

**File:** `app/Models/User.php`

- Add `welcomed_at` and `onboarding_dismissed_at` to `$fillable`
- Add both as `datetime` casts in the `casts()` method

## Step 3: Add Factory States

**File:** `database/factories/UserFactory.php`

Add two new states following existing `unverified()`/`founder()` pattern:
- `welcomed()` — sets `welcomed_at` to `now()`
- `onboardingDismissed()` — sets `onboarding_dismissed_at` to `now()`

## Step 4: Run `composer dump-autoload`

Required before creating new Livewire classes (per project rules).

## Step 5: Create WelcomeModal Component

```
php artisan make:livewire Onboarding/WelcomeModal --no-interaction
```

**Files created:**
- `app/Livewire/Onboarding/WelcomeModal.php`
- `resources/views/livewire/onboarding/welcome-modal.blade.php`

**PHP Component:**
- Public `bool $showWelcomeModal = false`
- `mount()`: sets `showWelcomeModal = true` if `Auth::user()->welcomed_at` is null
- `dismiss()`: updates user's `welcomed_at` to `now()`, sets boolean to false

**Blade View:**
- `<flux:modal wire:model.self="showWelcomeModal" :dismissible="false" class="md:w-96">`
- Welcome heading, app description, 3 preview steps with icons (academic-cap, arrow-up-tray, shield-check)
- Note about unlocking community trends
- "Let's go!" primary button calling `dismiss`

## Step 6: Create SetupChecklist Component

```
php artisan make:livewire Onboarding/SetupChecklist --no-interaction
```

**Files created:**
- `app/Livewire/Onboarding/SetupChecklist.php`
- `resources/views/livewire/onboarding/setup-checklist.blade.php`

**PHP Component:**
- Public booleans: `$hasSchool`, `$hasProgram`, `$hasPrivacy`, `$isDismissed`, `$isComplete`
- `mount()`: checks `onboarding_dismissed_at`; if not dismissed, runs `exists()` queries on `schools()`, `programs()`, `privacy()` relationships
- `dismiss()`: updates user's `onboarding_dismissed_at`, sets `$isDismissed = true`

**Blade View:**
- Only renders when `!$isDismissed && !$isComplete`
- Blue-tinted card (`border-blue-200 bg-blue-50` / dark mode equivalents) with clipboard icon + "Get Started" heading
- Dismiss button (ghost variant) in top-right
- 3 checklist items: each shows green `check-circle` when done (text with `line-through`), or a link when incomplete
- Links: `route('profile.edit')` for school and privacy, `route('addProgram')` for program upload
- All links use `wire:navigate`

## Step 7: Embed Components in Dashboard

**File:** `resources/views/dashboard.blade.php`

Add both components between the heading and `<livewire:dashboard />`:

```blade
<livewire:onboarding.welcome-modal />
<livewire:onboarding.setup-checklist />
```

The modal renders as an overlay (no layout impact). The checklist sits in the flex column with `gap-6` spacing.

## Step 8: Write Tests

Two Pest feature test files:

**`tests/Feature/Livewire/Onboarding/WelcomeModalTest.php`** (5 tests):
- New user sees the welcome modal
- Welcomed user does not see it
- Dismissing sets `welcomed_at` timestamp
- Modal renders on dashboard page
- Founder also sees it on first visit

**`tests/Feature/Livewire/Onboarding/SetupChecklistTest.php`** (8 tests):
- New user sees checklist with all items incomplete
- Checklist reflects school completion
- Checklist reflects program completion
- Checklist reflects privacy completion
- Checklist auto-hides when all items complete
- User can dismiss early (sets timestamp)
- Dismissed checklist stays hidden on next visit
- Checklist renders on dashboard page

## Step 9: Format & Test

```bash
php vendor/bin/pint --dirty
php artisan test --compact tests/Feature/Livewire/Onboarding/
php artisan test --compact tests/Feature/Livewire/DashboardTest.php
```

---

## Critical Files

| Action | File |
|--------|------|
| Modify | `app/Models/User.php` |
| Modify | `database/factories/UserFactory.php` |
| Modify | `resources/views/dashboard.blade.php` |
| Create | `database/migrations/*_add_onboarding_columns_to_users_table.php` |
| Create | `app/Livewire/Onboarding/WelcomeModal.php` |
| Create | `resources/views/livewire/onboarding/welcome-modal.blade.php` |
| Create | `app/Livewire/Onboarding/SetupChecklist.php` |
| Create | `resources/views/livewire/onboarding/setup-checklist.blade.php` |
| Create | `tests/Feature/Livewire/Onboarding/WelcomeModalTest.php` |
| Create | `tests/Feature/Livewire/Onboarding/SetupChecklistTest.php` |
