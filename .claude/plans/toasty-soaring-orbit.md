# Plan: Responsive card layout for Ensembles index on small screens

## Context
The ensembles index table currently has no responsive handling — columns get cut off on small viewports. We'll add a card layout below the `md` breakpoint that shows read-only data (no inline Type dropdown or A Cappella checkbox). Users edit via the Edit page on small screens. The existing table with inline editing remains for `md`+ screens.

## Changes

### 1. Update `resources/views/livewire/ensembles/index.blade.php`

**Hide table on small screens, show on md+:**
- Add `hidden md:block` to the existing table wrapper `<div>`

**Add card layout visible only below md:**
- Add a new `<div class="md:hidden space-y-3">` block before (or after) the table wrapper
- Loop over `$ensembles` with the same `@forelse`/`@empty` logic
- Each card is a `<div>` with rounded border, padding, and banding (matching existing dark mode conventions from the dashboard cards)
- Card contents (all read-only text):
  - **School** — muted text
  - **Ensemble Name** — primary text, bold
  - **Type** — label from `$ensemble->type->label()`
  - **A Cappella** — show badge/icon only when true
  - **Edit link** — pencil icon button for owned ensembles (same as table)
- Empty state: "No ensembles found." centered text

### 2. No backend changes
The Index component already provides all needed data (`$displayNames`, `$schoolDisplayNames`, `$ownedEnsembleIds`, etc.).

### 3. No new tests needed
This is a pure Blade/CSS presentation change. Existing Livewire tests already cover the component data and actions.

## Verification
- `php vendor/bin/pint --dirty`
- Visual check at narrow viewport (< 768px) — cards should appear
- Visual check at md+ viewport — table with inline editing should appear
- Verify dark mode styling on both layouts
