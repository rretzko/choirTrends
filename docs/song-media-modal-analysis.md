# Song Media Modal — Analysis & Implementation Plan

**Created:** 2026-04-15
**Status:** Analysis complete, ready for implementation

---

## Overview

Add a "Manage Media" modal to each song row on `programs/edit` that consolidates all media types into a single, organized interface. This replaces the current inline video/audio upload and adds two new capabilities: lyrics/text and sheet music (PDF).

---

## Feature Summary

### Media Types

| Type | Format | Storage | Searchable by Others? |
|------|--------|---------|----------------------|
| Audio/Video | mp4, mov, avi, wmv, mp3, wav, m4a, ogg, flac, aac, wma | File (local/S3) | No (visibility toggle) |
| Lyrics/Text | Textarea input or TXT/PDF upload (extracted to text) | Database (TEXT column) | Yes — compliant users can search lyrics text |
| Sheet Music | PDF, PNG, JPG | File (local/S3) | No — never visible to other users |

### Permission Model

The app has no traditional roles. Authorization is handled by authentication state and the existing `ProgramComplianceService` compliance gates. The three permission tiers map cleanly:

| Tier | Maps To | Media Access |
|------|---------|--------------|
| **Guest** | Unauthenticated visitor | No access to any user-uploaded media (audio, video, lyrics, sheet music) |
| **User** | Authenticated (`auth` + `verified` middleware) | Full CRUD on their own uploads only. Can see/play other users' audio/video if marked Public (existing behavior). Cannot see other users' lyrics or sheet music. |
| **Authorized User** | Authenticated + compliant (`canViewAll() === true`) | Everything a User gets, PLUS can search across all users' lyrics/text. Search results return song title + composer/arranger only — never the lyrics text itself, never sheet music files. |

**Key distinction:** "Authorized user" = a user who has met program upload compliance requirements (initial upload within 14 days + 2 programs/year). This is already enforced by `ProgramComplianceService` and the `ChecksProgramCompliance` trait used in `SongTitles/Index`.

---

## Database Changes

### New Table: `user_song_lyrics`

Stores lyrics/text per user per song. User-scoped to avoid copyright issues with shared `song_titles`.

```
user_song_lyrics
├── id (bigint, PK)
├── user_id (FK → users, cascade delete)
├── song_title_id (FK → song_titles, cascade delete)
├── content (LONGTEXT, FULLTEXT indexed)
├── source (ENUM: 'manual', 'uploaded') — how the text was entered
├── created_at
├── updated_at
└── UNIQUE(user_id, song_title_id)
```

**Why user-scoped:** The `song_titles` table is shared across all users. Attaching lyrics directly to `song_titles` would expose copyrighted text to everyone. A user-scoped table means each user stores their own copy, which is legally equivalent to a personal reference copy.

**Why FULLTEXT index:** The primary use case for lyrics is search. MySQL `LIKE '%keyword%'` does not use indexes and will degrade at scale. A `FULLTEXT` index on `content` enables `MATCH ... AGAINST` queries which are fast and support natural language search (relevance ranking) out of the box.

### New Table: `user_song_files`

Stores sheet music files and potentially other document types per user per song.

```
user_song_files
├── id (bigint, PK)
├── user_id (FK → users, cascade delete)
├── song_title_id (FK → song_titles, cascade delete)
├── file_path (string) — relative path in storage
├── original_filename (string) — user's original filename for display
├── mime_type (string) — validated server-side
├── file_size (unsigned integer) — bytes
├── type (ENUM: 'sheet_music') — extensible for future file categories
├── created_at
├── updated_at
```

**No unique constraint on (user_id, song_title_id):** A user may have multiple files per song (e.g., soprano part, full score, accompaniment reduction).

### Existing Table Changes: `program_song_title` (pivot)

No changes needed. Audio/video uploads remain on the pivot table exactly as they are now (`video_path`, `video_visibility`, `video_uploaded_at`). Moving them would break existing data and the `VideoController` serving logic.

---

## Storage

### File Paths

```
storage/app/private/
├── mp4s/
│   ├── programs/          ← existing concert videos
│   └── songs/             ← existing song audio/video
├── sheet-music/
│   └── {user_id}/
│       └── {song_title_id}/
│           └── {uuid}.{ext}   ← sheet music PDFs/images
```

User-scoped directory structure prevents filename collisions and makes bulk deletion (account deletion) straightforward.

### Size Limits

| Type | Max Size | Rationale |
|------|----------|-----------|
| Audio/Video | 500MB (existing) | Already configured in `config/livewire.php` |
| Lyrics text | 100,000 characters | Longest choral works (Messiah, B Minor Mass) are ~50K chars |
| Sheet music PDF | 50MB | Large orchestral scores with scanned pages |
| Lyrics file upload (TXT/PDF) | 5MB | Text extraction source file |

### S3 Consideration

Sheet music files should follow the same storage pattern as existing media — local disk for development, S3 for production (already configured in `config/filesystems.php`). The `VideoController` pattern of checking ownership then streaming/signing URLs is directly reusable.

---

## UI Design

### Edit Page Changes

The current inline video upload section on each song row gets replaced with a single "Media" button:

**Before (current):**
```
[Order] [Title] [Composer] [Arranger] [Audio indicator] [Visibility] [Delete file] [Remove song]
[   File input for upload   ] [Upload button]
```

**After:**
```
[Order] [Title] [Composer] [Arranger] [Media icons] [📎 Media] [Remove song]
```

- **Media icons:** Small indicator icons showing what's attached:
  - 🎵 (musical-note) = audio/video uploaded
  - 📄 (document-text) = lyrics attached
  - 📋 (document) = sheet music uploaded
- **📎 Media button:** Opens the modal. Shows a count badge if media exists.
- The button is only shown when `songTitleId` is set (song has been saved), matching the current behavior where uploads are only available after the song exists in the pivot.

### Modal Layout

```
┌─────────────────────────────────────────────────────┐
│  Manage Media — "Song Title Here"                 X │
├─────────────────────────────────────────────────────┤
│                                                     │
│  ── Audio / Video ──────────────────────────────    │
│                                                     │
│  [If uploaded:]                                     │
│    🎵 Audio uploaded    [Public/Private] [Delete]   │
│                                                     │
│  [If not uploaded:]                                 │
│    [File input: .mp4,.mov,.mp3,.wav,...]  [Upload]  │
│    [====== progress bar ======]                     │
│                                                     │
│  ── Lyrics / Text ──────────────────────────────    │
│                                                     │
│  [If lyrics exist:]                                 │
│    [Textarea with existing content, editable]       │
│    [Save] [Delete]                                  │
│                                                     │
│  [If no lyrics:]                                    │
│    [Textarea: paste or type lyrics]                 │
│    — or —                                           │
│    [File input: .txt,.pdf] (text will be extracted) │
│    [Save]                                           │
│                                                     │
│  ── Sheet Music ────────────────────────────────    │
│                                                     │
│  [List of uploaded files:]                          │
│    📄 soprano-part.pdf (2.1 MB)    [View] [Delete] │
│    📄 full-score.pdf (8.4 MB)      [View] [Delete] │
│                                                     │
│  [File input: .pdf,.png,.jpg]  [Upload]             │
│                                                     │
│  ⚠ Sheet music files are private to your account.  │
│                                                     │
├─────────────────────────────────────────────────────┤
│                                       [Close]       │
└─────────────────────────────────────────────────────┘
```

### Modal Implementation

- Use `flux:modal` component (already used elsewhere in the app).
- The modal should be a **child Livewire component** (`SongMediaManager`) rather than inline in the Edit component. This keeps the Edit component from growing more complex and isolates the media management state.
- The parent `Edit` component opens the modal by dispatching an event with `programId`, `songTitleId`, `ensembleIndex`, and `songIndex`.
- On close, the parent re-checks media state to update indicator icons.

### Lyrics Search on SongTitles/Index

Extend the existing search in `SongTitles/Index` (currently searches `song_title`, `composer`, `arranger`):

```php
// Existing search (app/Livewire/SongTitles/Index.php ~line 126-133)
if ($this->search !== '') {
    $searchTerm = '%'.$this->search.'%';
    $query->where(function ($q) use ($searchTerm) {
        $q->where('song_titles.song_title', 'like', $searchTerm)
            ->orWhere('composers.artist_name', 'like', $searchTerm)
            ->orWhere('arrangers.artist_name', 'like', $searchTerm);
    });
}

// Extended with lyrics search (only for compliant users)
if ($this->search !== '' && $this->canViewAll()) {
    // Add FULLTEXT search against lyrics from ALL users
    $query->orWhereHas('lyrics', function ($q) use ($searchTerm) {
        $q->whereRaw("MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)", [$this->search]);
    });
}
```

**What the search returns:** Song title, composer, arranger — the same columns already shown on the index page. The lyrics text itself is **never displayed** in search results. This is critical for copyright compliance — users can discover songs by content, but cannot read another user's copy of the lyrics.

**Search toggle:** Add a checkbox or toggle: "Search lyrics" (default off). This makes the search opt-in and avoids confusing results when users are just looking for a title match.

---

## Authorization Implementation

### File Serving (Sheet Music)

New controller method following the `VideoController` pattern:

```php
// SheetMusicController or extend VideoController
public function show(Request $request, UserSongFile $file): Response
{
    // Only the owner can view their sheet music — no public visibility option
    abort_unless($file->user_id === $request->user()->id, 403);

    // Serve from local or generate signed S3 URL
    // ... same pattern as VideoController
}
```

Route: `GET /media/sheet-music/{file}` — protected by `auth` + `verified` middleware.

### Lyrics Access

- **Write/Update/Delete:** Only the owning user, enforced by checking `user_id` on the `user_song_lyrics` record.
- **Search (read):** Compliant users can trigger FULLTEXT search across all `user_song_lyrics` rows, but only song metadata (title, composer, arranger) is returned — never the lyrics content. Non-compliant users and guests cannot search lyrics.
- **Direct read:** Only the owning user can see the actual lyrics text (in the modal).

### Catalog/Token Access

The existing catalog feature (public sharing via `catalog_token`) should **not** include lyrics or sheet music. Only audio/video with `Public` visibility should remain accessible via catalog, matching current behavior.

---

## Components and Files to Create/Modify

### New Files

| File | Purpose |
|------|---------|
| `database/migrations/xxxx_create_user_song_lyrics_table.php` | Lyrics table with FULLTEXT index |
| `database/migrations/xxxx_create_user_song_files_table.php` | Sheet music files table |
| `app/Models/UserSongLyrics.php` | Eloquent model |
| `app/Models/UserSongFile.php` | Eloquent model |
| `app/Livewire/Programs/SongMediaManager.php` | Modal Livewire component |
| `resources/views/livewire/programs/song-media-manager.blade.php` | Modal template |
| `app/Http/Controllers/SheetMusicController.php` | File serving controller |
| `tests/Feature/Livewire/Programs/SongMediaManagerTest.php` | Component tests |
| `tests/Feature/SongLyricsSearchTest.php` | Lyrics search authorization tests |

### Modified Files

| File | Changes |
|------|---------|
| `app/Livewire/Programs/Edit.php` | Remove inline video upload logic → delegate to modal. Add media indicator state. Keep `programVideo` (concert-level) as-is. |
| `resources/views/livewire/programs/edit.blade.php` | Replace inline upload UI with Media button + indicator icons. Add modal component inclusion. |
| `app/Livewire/SongTitles/Index.php` | Add lyrics FULLTEXT search for compliant users. Add "search lyrics" toggle. |
| `resources/views/livewire/song-titles/index.blade.php` | Add search lyrics toggle UI. |
| `app/Models/SongTitle.php` | Add `lyrics()` HasMany relationship (through user_song_lyrics). |
| `app/Models/User.php` | Add `songLyrics()` and `songFiles()` HasMany relationships. |
| `routes/web.php` | Add sheet music serving route. |

---

## Risks and Mitigations

### Copyright (High Risk)

**Risk:** Storing copyrighted lyrics and sheet music exposes the platform to DMCA claims.

**Mitigations:**
1. All lyrics and sheet music are user-scoped — never shared or displayed to other users.
2. Lyrics search returns only metadata (title, composer, arranger), never the text itself.
3. Sheet music has no public visibility option — strictly owner-only access.
4. Terms of Service must be updated: users affirm they have the right to store uploaded content for personal reference.
5. Implement DMCA takedown response process (contact email, takedown within 24 hours).
6. Consider a "first use" acknowledgment dialog when a user first uploads lyrics or sheet music.

### Storage Growth (Medium Risk)

**Risk:** Sheet music PDFs (1-10MB each) across many users will consume significant storage.

**Mitigations:**
1. Use S3 for production (already configured).
2. Set per-file size limits (50MB sheet music, 5MB lyrics upload).
3. Consider a per-user storage quota (e.g., 500MB for sheet music). Can be added later if needed.
4. File deduplication is NOT recommended — sharing files across users creates copyright exposure.

### Search Performance (Low Risk)

**Risk:** FULLTEXT search on a growing lyrics table could slow down.

**Mitigations:**
1. MySQL FULLTEXT indexes handle millions of rows efficiently.
2. Search is opt-in (toggle), so it doesn't affect default queries.
3. Only compliant users trigger lyrics search, limiting concurrent load.

### Text Extraction from PDF (Low Risk)

**Risk:** Users upload a PDF of lyrics, and text extraction fails or produces garbage.

**Mitigations:**
1. Prefer the textarea input — make it the primary/default option.
2. For PDF upload, use the existing `ProgramContentExtractor` pattern (Claude AI extraction) or PHP's `pdftotext` for simple text PDFs.
3. Show extracted text in the textarea for user review/correction before saving.

### Data Integrity on Song Deletion (Low Risk)

**Risk:** When a song is removed from a program, orphaned lyrics/files remain.

**Mitigation:** Lyrics and sheet music are tied to `song_title_id` (not the pivot), so they persist even if the song is removed from a specific program. This is correct behavior — the user's lyrics and sheet music are part of their personal library, not tied to a specific program performance.

### Account Deletion (Low Risk)

**Risk:** Files remain on disk after user account deletion.

**Mitigation:** Cascade deletes on `user_id` FK handle database cleanup. Add a model observer or event listener on User deletion to purge the `sheet-music/{user_id}/` directory from storage.

---

## Implementation Sequence

### Phase 1: Modal with Audio/Video (refactor only, no new features)

1. Create `SongMediaManager` Livewire component.
2. Move existing audio/video upload logic from `Edit.php` into the new component.
3. Update `edit.blade.php` — replace inline upload with Media button + modal.
4. Write tests to verify existing audio/video behavior is preserved.
5. Verify UI in browser.

### Phase 2: Lyrics/Text

1. Create `user_song_lyrics` migration and model.
2. Add lyrics textarea and file upload to the modal.
3. Add lyrics CRUD methods to `SongMediaManager`.
4. Extend `SongTitles/Index` search with FULLTEXT lyrics search (compliant users only).
5. Add "Search lyrics" toggle to the song titles index view.
6. Write tests for lyrics CRUD and search authorization.

### Phase 3: Sheet Music

1. Create `user_song_files` migration and model.
2. Add sheet music upload/list/delete to the modal.
3. Create `SheetMusicController` for file serving (owner-only).
4. Add route and wire up the "View" button in the modal.
5. Write tests for upload, access control, and deletion.

### Phase 4: Polish

1. Add media indicator icons on the edit page song rows.
2. Add first-use copyright acknowledgment dialog.
3. Update Terms of Service language.
4. Add storage cleanup on User deletion.
5. Run full test suite.

---

## Open Questions for Decision

1. **Lyrics file upload:** Should we support extracting text from uploaded PDF/TXT files, or just offer the textarea? The textarea is simpler and avoids extraction issues. PDF extraction can be added later.

2. **Storage quota:** Should there be a per-user limit on sheet music storage? Not critical for launch but worth deciding before it becomes a problem.

3. **Concert-level video:** The program-level concert video upload currently lives inline on the edit page (separate from per-song media). Should it stay there or also move into a program-level modal? Recommendation: leave it as-is — it's a single upload, not per-song, and doesn't clutter the page.

4. **Lyrics visibility nuance:** Should a user be able to view their own lyrics from the `SongTitles/Index` page (e.g., click to expand), or only from the edit modal? A quick-view on the index page could be useful for reference without navigating to a program.

5. **My Library page:** Should we build a standalone `/library` page for browsing all uploaded sheet music across all songs, independent of programs? This could be a Phase 5 addition.
