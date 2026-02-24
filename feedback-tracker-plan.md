# Feedback Tracker Implementation Plan

## Overview

Split the feedback system into two distinct pages:

### 1. User Feedback (`/feedback`) — Two tabs

| Tab | Name | Purpose |
|-----|------|---------|
| 1 | **Report** | Submit new feedback (replaces `/feedback/create`) |
| 2 | **History** | Browse/filter own feedback; edit own submissions; comment on own items |

**URL structure:** `/feedback?tab=report`, `/feedback?tab=history` (default)

### 2. Founder Issues (`/founder/issues`) — New page under Founder section

A dedicated issue management page visible only to the founder, protected by `EnsureUserIsFounder` middleware.

| Feature | Description |
|---------|-------------|
| Browse all feedback | Filterable list of all feedback from all users |
| Status management | Change status (Open, Pending, Wip, Closed) |
| Comments | View all comments, add founder comments |
| Effort tracking | Live timer + manual time entry; total effort display |
| Detail view | Click a feedback item to see full details in a detail panel |

---

## Phase 1: Database — New `feedback_efforts` Table

### Migration: `create_feedback_efforts_table`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | auto-increment |
| feedback_id | foreignId | constrained to `feedbacks`, cascadeOnDelete |
| started_at | datetime | when work began |
| stopped_at | datetime | nullable; null = timer is running |
| timestamps | | |

### Model: `FeedbackEffort`

- **Fillable:** `feedback_id`, `started_at`, `stopped_at`
- **Casts:** `started_at` → `datetime`, `stopped_at` → `datetime`
- **Relationships:** `feedback(): BelongsTo`
- **Factory:** Default state with `started_at` = recent past, `stopped_at` = `started_at` + random minutes

### Model changes to `Feedback`

- Add relationship: `efforts(): HasMany(FeedbackEffort)`
- Add helper method: `totalEffortMinutes(): int` — sums duration of all completed effort entries

### Model changes to `FeedbackComment`

- No structural changes. Comments from non-founder users will now be allowed (on own feedback only).

---

## Phase 2: Refactor User Feedback Page (`Feedback\Index`)

### Merge `Feedback\Create` into `Feedback\Index`

The create form moves into the **Report** tab. The `Create` component class and its blade view will be deleted.

### Route changes in `web.php`

- **Remove:** `Route::get('feedback/create', ...)->name('feedback.create')`
- **Keep:** `Route::get('feedback', ...)->name('feedback.index')`
- **Add redirect:** `Route::redirect('feedback/create', '/feedback?tab=report')` (preserves old bookmarks)

### Sidebar update

- Update the "Submit Feedback" button in `sidebar.blade.php` to point to `/feedback?tab=report` instead of `route('feedback.create')`.

### Component properties

```php
// Tab control
#[Url]
public string $tab = 'history';

// Existing (keep)
public string $filterType = '';
public string $filterScope = 'my';
public string $sortBy = 'created_at';
public string $sortDirection = 'desc';

// Report tab (merged from Create component)
public string $fromPage = '';
public string $type = 'Bug';
public string $body = '';
public $file = null; // TemporaryUploadedFile

// History tab — editing
public ?int $editingFeedbackId = null;
public string $editBody = '';
public string $editType = '';
public $editFile = null;

// History tab — user comments
public string $userComment = '';
public ?int $commentingFeedbackId = null;
```

### Component methods

**Report tab:**
- `setType(string $type): void` — set feedback type
- `submit(): void` — validate, create feedback, email founder, flash success, switch to history tab

**History tab:**
- `startEditing(int $feedbackId): void` — load feedback into edit properties (owner check)
- `cancelEditing(): void` — reset edit properties
- `saveEdit(): void` — validate and update feedback body/type/file (owner check)
- `startCommenting(int $feedbackId): void` — set commentingFeedbackId (owner check)
- `cancelCommenting(): void` — reset
- `submitUserComment(): void` — validate and create comment on own feedback

**Remove these methods** (moved to Founder\Issues):
- `showDetails()` — replaced by inline editing on History tab
- `updateStatus()` — moved to Founder\Issues
- `addComment()` — split into `submitUserComment()` (here) and founder comment (Founder\Issues)

**Keep:**
- `sort()` — unchanged
- `render()` — refactored (remove founder-specific logic, add tab support)
- `getDisplayName()` — unchanged

### Add trait

- `use WithFileUploads` (from Create component)

### Blade view — Tabbed layout

**Tab bar:** Use Flux buttons styled as tabs. Two buttons:
- **Report** — visible to all
- **History** — visible to all (default)

Active tab: `variant="primary"`, inactive: `variant="ghost"`.

**Report tab (`tab === 'report'`):**
Same form as current `create.blade.php`:
- Reported By (disabled input)
- From Page (text input)
- Request Type (4 buttons: Bug, Enhancement, Kudo, Comment)
- Request (textarea)
- Upload File or Image (file input)
- Submit button

**History tab (`tab === 'history'`):**

Filter bar: Same as current (type filter + scope filter).

Table columns: Date, Type, Submitted By, Request, Comments, Status, Actions.
- Remove `wire:click="showDetails()"` row click behavior
- Actions column:
  - **Edit** button (pencil icon) — only if `$feedback->user_id === auth()->id()`
  - **Comment** button (chat icon) — only if `$feedback->user_id === auth()->id()`

Inline edit: When `$editingFeedbackId === $feedback->id`, replace the row with an edit form:
- Type selector (4 buttons)
- Body textarea
- File upload (current file shown if exists)
- Save / Cancel buttons

Inline comments: When `$commentingFeedbackId === $feedback->id`, show below the row:
- Existing comments list
- New comment textarea + submit button

**Remove the `feedback-details` modal entirely.**

---

## Phase 3: New Founder Issues Page (`Founder\Issues`)

### Route in `web.php` (inside founder middleware group)

```php
Route::get('founder/issues', App\Livewire\Founder\Issues::class)->name('founder.issues');
```

### Sidebar update

Add "Issues" link to the founder section of the sidebar (alongside Dashboard, Duplicates, etc.).

### Component: `App\Livewire\Founder\Issues`

**Properties:**
```php
// Filters
public string $filterType = '';
public string $filterStatus = '';
public string $sortBy = 'created_at';
public string $sortDirection = 'desc';

// Selected feedback detail
public ?Feedback $selectedFeedback = null;
public string $selectedStatus = '';
public string $newComment = '';

// Effort tracking
public ?string $manualStartTime = null;
public ?string $manualStopTime = null;
```

**Methods:**
- `sort(string $column): void` — toggle sort direction
- `selectFeedback(int $feedbackId): void` — load feedback with user, comments, efforts
- `closeDetail(): void` — deselect
- `updateStatus(): void` — update status on selected feedback
- `addComment(): void` — validate and add founder comment
- `startTimer(): void` — create FeedbackEffort with `started_at = now()`, `stopped_at = null`
- `stopTimer(): void` — find running effort, set `stopped_at = now()`
- `addManualEffort(): void` — validate start/stop times, create FeedbackEffort
- `render(): View` — query all feedback with relationships, pass to view

### Blade view layout

**Left panel: Feedback list**
- Filter bar: type filter + status filter
- Table: Date, Type, Submitter, Request (truncated), Status, Effort (total time)
- Sortable columns: date, type, status
- Click row to select → loads into detail panel

**Right panel: Detail** (when `$selectedFeedback` is set)
- Full feedback info: submitter, date, type, from page, body, attached file
- **Status dropdown** — saves immediately via `wire:model.live` + `wire:change`
- **Comments section:**
  - All comments (user + founder) in chronological order
  - Add comment textarea + button
- **Effort tracking section:**
  - **Total effort** — formatted as hours:minutes
  - **Timer controls:**
    - No timer running → "Start Timer" button
    - Timer running → "Stop Timer" button + elapsed indicator
  - **Manual entry:** Two `datetime-local` inputs (start, stop) + "Add Entry" button

---

## Phase 4: Tests

### Update `CreateFeedbackTest.php`

Rewrite to test the Report tab within `Feedback\Index` instead of the removed `Create` component. Same coverage:
- Tab renders the form
- Submission creates record, sends email, switches to history tab
- Validation (body required, min 5, valid type, file max 5MB)
- File upload stores correctly
- `setType` toggles type
- All four types submittable
- `fromPage` optional

### Update `FeedbackIndexTest.php`

Update existing tests for History tab, add new tests:
- Tab defaults to `history`
- Tab switching works via URL parameter
- Edit own feedback: loads edit form, saves changes (body, type, file)
- Cannot edit another user's feedback
- Comment on own feedback: creates comment
- Cannot comment on another user's feedback
- Redirect from old `/feedback/create` route works
- Remove founder-specific tests (status update, founder comment) — moved to Issues tests

### New: `tests/Feature/Founder/IssuesTest.php`

- Page renders for founder, redirects non-founder
- Guest redirected to login
- Lists all feedback from all users
- Filter by type
- Filter by status
- Sort by date/type/status
- Select feedback shows detail panel
- Update status
- Add comment
- Start timer creates effort entry with null `stopped_at`
- Stop timer sets `stopped_at` on running entry
- Cannot start timer if one is already running for this feedback
- Add manual effort entry with valid start/stop
- Manual effort validation (stop must be after start, both required)
- Total effort displays correctly
- Display names always show real names (founder sees all)

---

## Phase 5: Cleanup

- Run `php vendor/bin/pint --dirty` for code formatting
- Run `composer dump-autoload` after creating new model/factory
- Run `php artisan test --compact` on all feedback + founder tests
- Delete `app/Livewire/Feedback/Create.php`
- Delete `resources/views/livewire/feedback/create.blade.php`
- Update `ProjectKnowledge.md` with new architecture

---

## Implementation Order

| Step | Task | Dependencies |
|------|------|-------------|
| 1 | Create migration for `feedback_efforts` table | — |
| 2 | Create `FeedbackEffort` model + factory | Step 1 |
| 3 | Add `efforts()` relationship + `totalEffortMinutes()` to `Feedback` model | Step 2 |
| 4 | Refactor `Feedback\Index` component (merge Create, add edit/comment methods) | Step 3 |
| 5 | Build tabbed blade view — Report tab + History tab | Step 4 |
| 6 | Update routes + sidebar | Step 5 |
| 7 | Create `Founder\Issues` component | Step 3 |
| 8 | Build `Founder\Issues` blade view | Step 7 |
| 9 | Add Issues route + sidebar link (founder section) | Step 8 |
| 10 | Update/rewrite `CreateFeedbackTest.php` | Step 5 |
| 11 | Update `FeedbackIndexTest.php` | Step 5 |
| 12 | Create `Founder/IssuesTest.php` | Step 8 |
| 13 | Cleanup: Pint, delete old files, full test suite | Steps 10-12 |

---

## Files Changed/Created

| Action | File |
|--------|------|
| **Create** | `database/migrations/xxxx_create_feedback_efforts_table.php` |
| **Create** | `app/Models/FeedbackEffort.php` |
| **Create** | `database/factories/FeedbackEffortFactory.php` |
| **Create** | `app/Livewire/Founder/Issues.php` |
| **Create** | `resources/views/livewire/founder/issues.blade.php` |
| **Create** | `tests/Feature/Founder/IssuesTest.php` |
| **Edit** | `app/Models/Feedback.php` (add relationship + helper) |
| **Edit** | `app/Livewire/Feedback/Index.php` (merge Create, add edit/comment, remove founder logic) |
| **Edit** | `resources/views/livewire/feedback/index.blade.php` (tabbed rewrite) |
| **Edit** | `routes/web.php` (remove create route, add redirect, add founder/issues) |
| **Edit** | `resources/views/components/layouts/app/sidebar.blade.php` (update feedback link, add Issues) |
| **Edit** | `tests/Feature/Feedback/CreateFeedbackTest.php` (rewrite for Report tab) |
| **Edit** | `tests/Feature/Feedback/FeedbackIndexTest.php` (update for History tab) |
| **Delete** | `app/Livewire/Feedback/Create.php` |
| **Delete** | `resources/views/livewire/feedback/create.blade.php` |
