# Digital Programs — Requirements & Implementation Plan

> **Status:** Approved, implementation starting Phase 1
> **Created:** 2026-06-03

---

## Background

A customer request to add a "digital program" publishing tool to ChoirTrends. Directors currently spend hours formatting programs in Word, Publisher, or Adobe. ChoirTrends already holds all the musical data; this feature adds a display/publication layer on top of existing Program records, generating a unique public URL and QR code for audience access at performances.

**Primary audience for the display page:** cell phone in dim light (performance environment, low brightness). Second: tablet. Third (testing/archival): desktop monitor.

---

## Terminology

| Term | Meaning |
|---|---|
| **Digital Program** | The publishable, public-facing concert program built on top of a ChoirTrends `Program` record |
| **Guided** | Step-by-step wizard path (new/less-experienced users) |
| **Power User** | Single full-form path for self-directed users |

---

## Text Data Available from the Existing Program Entity

| Layer | Fields |
|---|---|
| **Program** | `event_name`, `event_date`, `director_name`, `school.school_name`, `school.abbreviation`, `school.geo_state`, `school.country` |
| **Ensemble** | `ensemble_name`, `ensemble_director` (pivot), `a_cappella`, `ensemble_sort_order` |
| **Song** | `song_title`, `composer.artist_name`, `arranger.artist_name`, `notes` (rich HTML), `sort_order` |
| **Supplemental** | `user_song_lyrics.content`, `user_song_lyrics.source` |

---

## New Data Required (Not in Current Schema)

### `digital_programs` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK → users | |
| `program_id` | FK → programs, nullable | null when director builds from scratch; populated after Program is created |
| `slug` | string(8), unique | auto-generated, never changes; used for public URL and QR code |
| `theme` | string (enum) | WinterConcert, SpringFestival, Graduation, Holiday, Formal, Minimalist |
| `is_published` | boolean, default false | |
| `welcome_message` | text, nullable | Director's foreword |
| `acknowledgments` | text, nullable | Thank-you section |
| `sponsor_text` | text, nullable | Patron/sponsor recognition |
| `intermission_after_ensemble` | int, nullable | `ensemble_sort_order` value after which intermission banner appears |
| `print_orientation` | string (enum) | Portrait, Landscape |
| `student_names_acknowledged` | boolean, default false | Director must confirm before entering roster |
| timestamps | | |

### `digital_program_rosters` table

One row = one student name within an ensemble on a digital program.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `digital_program_id` | FK → digital_programs | |
| `ensemble_id` | FK → ensembles, nullable | null = unensembled section |
| `voice_part` | string, nullable | Predefined list (see below) + free-text Other |
| `student_name` | string | Single field; director formats as desired (full name, first only, etc.) |
| `sort_order` | int | Within voice part group |
| timestamps | | |

**Predefined voice parts:** Soprano I, Soprano II, Alto, Tenor, Baritone, Bass, Treble, Cambiata, Unison — plus free-text Other.

### `digital_program_honors` table

The footnote legend for superscript honors (per ensemble, numbers restart per ensemble).

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `digital_program_id` | FK → digital_programs | |
| `ensemble_id` | FK → ensembles, nullable | |
| `label` | string(100) | "Section Leader", "All-State Choir", "Officer", etc. (free text) |
| `sort_order` | int | Determines superscript number (1, 2, 3…) |
| timestamps | | |

### `digital_program_roster_honor` pivot table

Which students hold which honors.

| Column | Type |
|---|---|
| `digital_program_roster_id` | FK → digital_program_rosters |
| `digital_program_honor_id` | FK → digital_program_honors |
| Composite PK | |

### `digital_program_song_settings` table

Per-song display configuration.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `digital_program_id` | FK → digital_programs | |
| `song_title_id` | FK → song_titles | |
| `show_lyrics` | boolean, default false | Opt-in per song |
| `lyrics_copyright_acknowledged` | boolean, default false | Program-level disclaimer (set once) |
| timestamps | | |

---

## Features & Rules

### Lyrics Display
- Opt-in per song (director chooses which songs show lyrics)
- A single **program-level disclaimer** must be acknowledged before any lyrics are enabled:
  > *"I confirm that I have the right to display these lyrics publicly, or that they are in the public domain."*
- Stored as `lyrics_copyright_acknowledged` on `digital_programs`

### Student Roster
- Optional — program can be published with no roster
- Director must acknowledge before entering names:
  > *"I acknowledge that the names I enter will be publicly visible on this digital program. I will not enter any personally identifying information beyond student names (no ID numbers, contact details, addresses, or dates of birth)."*
- Voice parts: predefined list (Soprano I, Soprano II, Alto, Tenor, Baritone, Bass, Treble, Cambiata, Unison) + free-text Other
- Roster is displayed grouped by voice part within each ensemble section

### Honors / Superscripts
- Defined per ensemble; numbers restart at 1 per ensemble
- Display format:

  ```
  CONCERT CHOIR — Director: Jane Smith
    Soprano:  Mary Adams¹², Susan Brown²
    Alto:     Kim Davis¹³, Carol Evans

    ¹ Section Leader   ² All-State Choir   ³ Officer
  ```

- Director defines honor labels (free text) for each ensemble; sort order = superscript number
- Honors can be reordered (reorder changes the superscript numbers)
- Deleting a honor strips it from all students assigned to it

### URL & QR Code
- Public URL: `/p/{slug}` (8-character random string, unique, permanent — never expires)
- QR code displayed in director's configuration view for printing/sharing
- QR code also embedded at bottom of public display page (for desktop/tablet viewers)
- No authentication required to view the public page

### Themes (CSS-only, Phase 1)
Six built-in themes, implemented as Tailwind CSS color/typography variations:

| Theme | Character |
|---|---|
| WinterConcert | Cool blues, silver, elegant serif |
| SpringFestival | Soft greens, floral warmth |
| Graduation | Navy/gold, formal |
| Holiday | Deep red, gold accents |
| Formal | Black/white/charcoal, minimal |
| Minimalist | Off-white, light grey, clean sans-serif |

### Printing
- `@media print` stylesheet
- `@page { size: letter portrait; }` or `@page { size: letter landscape; }` driven by `print_orientation`
- Hide: navigation, edit buttons, QR code (optional — may keep for physical copies)
- Full lyrics visible in print even if collapsed on screen
- Page breaks between ensembles
- Typography shifts to a more formal/print-friendly variant

### Starting Point (two paths in Guided wizard Step 1)
- **Select existing Program** — dropdown of director's existing ChoirTrends Programs
- **Enter new program** — wizard continues to capture event/song data, creating a ChoirTrends `Program` record as part of the flow

---

## User Paths

### Guided Wizard (`DigitalPrograms\GuidedWizard`)
Multi-step Livewire component (`$step` integer 1–6):

| Step | Content |
|---|---|
| 1 — Start | Select existing Program OR enter new program data |
| 2 — Style | Theme picker (visual swatches), print orientation toggle |
| 3 — Program Content | Welcome message, acknowledgments, sponsor text, intermission position |
| 4 — Song Settings | Per-song lyrics opt-in; program-level disclaimer (if any lyrics enabled) |
| 5 — Roster & Honors | Roster acknowledgment; per-ensemble: define honors, then add students with voice part + honor assignments |
| 6 — Preview & Publish | Live preview + unique URL display + QR code + Publish / Save as Draft |

### Power User Form (`DigitalPrograms\PowerUserForm`)
All sections on one page with anchor-linked sidebar navigation. Live preview panel on desktop (hidden on mobile). Same acknowledgment checkboxes inline with their respective sections.

---

## Route Map

```
GET  /digital-programs                      my digital programs index (auth)
GET  /digital-programs/create               path chooser: Guided vs Power User
GET  /digital-programs/create/guided        GuidedWizard component
GET  /digital-programs/create/pro           PowerUserForm component
GET  /digital-programs/{digitalProgram}     show/configure existing digital program (auth, owner only)
GET  /p/{slug}                              public program view (no auth)
GET  /founder/create-program               Founder test entry (founder middleware)
```

Named routes: `digital-programs.index`, `digital-programs.create`, `digital-programs.create.guided`,
`digital-programs.create.pro`, `digital-programs.show`, `program.public`, `founder.createProgram`

---

## New Models

| Model | Table | Key Relationships |
|---|---|---|
| `DigitalProgram` | `digital_programs` | `belongsTo` User, Program; `hasMany` Roster, Honor, SongSetting |
| `DigitalProgramRoster` | `digital_program_rosters` | `belongsTo` DigitalProgram, Ensemble; `belongsToMany` Honor |
| `DigitalProgramHonor` | `digital_program_honors` | `belongsTo` DigitalProgram, Ensemble; `belongsToMany` Roster |
| `DigitalProgramSongSetting` | `digital_program_song_settings` | `belongsTo` DigitalProgram, SongTitle |

---

## Implementation Phases

### Phase 1 — Data Layer ✅ COMPLETE
- [x] Migration: `digital_programs`
- [x] Migration: `digital_program_rosters`
- [x] Migration: `digital_program_honors`
- [x] Migration: `digital_program_roster_honor` (pivot)
- [x] Migration: `digital_program_song_settings`
- [x] Model: `DigitalProgram` (with slug generation on creating)
- [x] Model: `DigitalProgramRoster`
- [x] Model: `DigitalProgramHonor`
- [x] Model: `DigitalProgramSongSetting`
- [x] `HasDigitalProgramState` trait (shared by Guided + Power User)
- [x] Founder sidebar link → `founder.createProgram`

### Phase 2 — Guided Wizard ✅ COMPLETE
- [x] Livewire: `DigitalPrograms\GuidedWizard` (6 steps)
- [x] Route `digital-programs.create.guided`
- [x] Step partials (`wizard/ensemble-section.blade.php`)

### Phase 3 — Power User Form ✅ COMPLETE
- [x] Livewire: `DigitalPrograms\PowerUserForm`
- [x] Route `digital-programs.create.pro`
- [x] Sticky action bar, section anchors, desktop preview sidebar

### Phase 4 — Public Display Page ✅ COMPLETE
- [x] `DigitalProgramPublicController` (single-action, 6 eager loads, no N+1)
- [x] Route `program.public` → `GET /p/{slug}`
- [x] 6 CSS themes via `--dp-*` custom properties
- [x] Mobile-first layout, dim-light optimized
- [x] Inline SVG QR code via BaconQrCode v3 (already installed)
- [x] Song list, roster with superscripts, honor legend, intermission banner
- [x] Lyrics display (opt-in per song)

### Phase 5 — Print Stylesheet ✅ COMPLETE
- [x] `@page { size: letter portrait|landscape; margin: 0.625in 0.75in; }`
- [x] `print-color-adjust: exact` — theme colors preserved in PDF/colour print
- [x] Landscape → two-column `main` layout (Blade-conditional CSS)
- [x] `song-item` break-inside avoid; `roster-section` break-inside avoid
- [x] QR code resized to 100×100 for print footer
- [x] "Print Program" button (`onclick="window.print()"`)

### Phase 6 — Digital Programs Index
- [ ] `DigitalPrograms\Index` — list, publish/unpublish, copy URL, delete

### Phase 7 — Tests
- [ ] Feature tests: slug generation, publish/unpublish, ownership gate, public route (no auth), lyrics gate, honor numbering
- [ ] Browser test: guided wizard happy path → confirm public URL loads

---

## QR Code
Used `BaconQrCode` v3.1.1 directly (already installed as a transitive dependency).
`simplesoftwareio/simple-qrcode` was not used — it requires BaconQrCode ^2 which conflicts.
Usage: `BaconQrCode\Writer` + `ImageRenderer` + `SvgImageBackEnd` → inline SVG embedded in view.

---

## Open Design Decisions (resolved)
- Lyrics: program-level disclaimer, opt-in per song ✓
- Themes: CSS-only for Phase 1 ✓
- URL lifetime: permanent, never expires ✓
- Starting point: both (select existing OR create new) ✓
- Voice parts: predefined list + free-text Other ✓
- Honors: per ensemble, numbers restart per ensemble ✓
- Honors label: free text ✓
