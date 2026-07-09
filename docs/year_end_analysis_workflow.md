# Year-End Programming Trends Analysis Workflow

Run this analysis each July after directors have had a chance to upload their final programs. The school year runs **September 1 – July 31**. Winter semester: Sep–Jan. Spring semester: Feb–Jul.

The minimum threshold for "trends" is **3+ performances**. Report top 5 per category.

**Note on ensemble type data:** Historically ~82% of performances have `type = 'Unknown'` on the ensemble. Flag this caveat in the analysis but still report the typed breakdown for context. Ensemble type insights are illustrative, not statistically robust.

**Note on SQL tool:** The `database-query` MCP tool blocks queries with `--` comments. Write all queries without inline comments.

---

## Step 1 — Overall Dataset Stats

**Note on total_directors:** `COUNT(DISTINCT p.user_id)` counts distinct `programs.user_id` regardless of `users.role` — if Assistant-role accounts (`parent_user_id` set) start logging programs directly, this figure will include them rather than just Directors.

```sql
SELECT
  COUNT(DISTINCT p.id) as total_programs,
  COUNT(DISTINCT p.school_id) as total_schools,
  COUNT(DISTINCT p.user_id) as total_directors,
  COUNT(DISTINCT pst.song_title_id) as unique_titles,
  COUNT(pst.song_title_id) as total_performances
FROM programs p
JOIN program_song_title pst ON p.id = pst.program_id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-07-31'
```

## Step 2 — Geography

```sql
SELECT s.geo_state, s.country, COUNT(DISTINCT p.id) as programs, COUNT(DISTINCT p.school_id) as schools
FROM programs p
JOIN schools s ON p.school_id = s.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-07-31'
GROUP BY s.geo_state, s.country
ORDER BY programs DESC
```

## Step 3 — Program Calendar by Month

```sql
SELECT DATE_FORMAT(p.event_date, '%Y-%m') as month, COUNT(DISTINCT p.id) as programs
FROM programs p
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-07-31'
GROUP BY month
ORDER BY month
```

## Step 4 — Ensemble Type Breakdown

```sql
SELECT e.type, COUNT(DISTINCT e.id) as unique_ensembles, COUNT(pst.program_id) as total_performances,
  SUM(e.a_cappella) as a_cappella_count
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN ensembles e ON pst.ensemble_id = e.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-07-31'
GROUP BY e.type
ORDER BY total_performances DESC
```

## Step 5 — Top Composers (Overall)

```sql
SELECT a.artist_name, COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
JOIN artists a ON st.composer_id = a.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-07-31'
  AND st.composer_id IS NOT NULL
GROUP BY a.id, a.artist_name
HAVING performances >= 3
ORDER BY performances DESC
LIMIT 20
```

## Step 6 — Top Arrangers (Overall)

```sql
SELECT a.artist_name, COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
JOIN artists a ON st.arranger_id = a.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-07-31'
  AND st.arranger_id IS NOT NULL
GROUP BY a.id, a.artist_name
HAVING performances >= 3
ORDER BY performances DESC
LIMIT 20
```

## Step 7 — Most-Performed Titles (Overall)

```sql
SELECT st.song_title, a_comp.artist_name as composer, a_arr.artist_name as arranger,
  COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
LEFT JOIN artists a_comp ON st.composer_id = a_comp.id
LEFT JOIN artists a_arr ON st.arranger_id = a_arr.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-07-31'
GROUP BY st.id, st.song_title, composer, arranger
HAVING performances >= 3
ORDER BY performances DESC
LIMIT 20
```

## Step 8 — Winter Composers (Sep–Jan)

```sql
SELECT a.artist_name, COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
JOIN artists a ON st.composer_id = a.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-01-31'
  AND st.composer_id IS NOT NULL
GROUP BY a.id, a.artist_name
ORDER BY performances DESC
LIMIT 10
```

## Step 9 — Spring Composers (Feb–Jul)

```sql
SELECT a.artist_name, COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
JOIN artists a ON st.composer_id = a.id
WHERE p.event_date BETWEEN '2026-02-01' AND '2026-07-31'
  AND st.composer_id IS NOT NULL
GROUP BY a.id, a.artist_name
ORDER BY performances DESC
LIMIT 10
```

## Step 10 — Winter Arrangers (Sep–Jan)

```sql
SELECT a.artist_name, COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
JOIN artists a ON st.arranger_id = a.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-01-31'
  AND st.arranger_id IS NOT NULL
GROUP BY a.id, a.artist_name
ORDER BY performances DESC
LIMIT 10
```

## Step 11 — Spring Arrangers (Feb–Jul)

```sql
SELECT a.artist_name, COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
JOIN artists a ON st.arranger_id = a.id
WHERE p.event_date BETWEEN '2026-02-01' AND '2026-07-31'
  AND st.arranger_id IS NOT NULL
GROUP BY a.id, a.artist_name
ORDER BY performances DESC
LIMIT 10
```

## Step 12 — Winter Titles (Sep–Jan)

```sql
SELECT st.song_title, a_comp.artist_name as composer, a_arr.artist_name as arranger,
  COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
LEFT JOIN artists a_comp ON st.composer_id = a_comp.id
LEFT JOIN artists a_arr ON st.arranger_id = a_arr.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-01-31'
GROUP BY st.id, st.song_title, composer, arranger
ORDER BY performances DESC
LIMIT 10
```

## Step 13 — Spring Titles (Feb–Jul)

```sql
SELECT st.song_title, a_comp.artist_name as composer, a_arr.artist_name as arranger,
  COUNT(*) as performances, COUNT(DISTINCT p.id) as programs
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
LEFT JOIN artists a_comp ON st.composer_id = a_comp.id
LEFT JOIN artists a_arr ON st.arranger_id = a_arr.id
WHERE p.event_date BETWEEN '2026-02-01' AND '2026-07-31'
GROUP BY st.id, st.song_title, composer, arranger
ORDER BY performances DESC
LIMIT 10
```

## Step 14 — Composers by Ensemble Type (typed ensembles only)

```sql
SELECT e.type, a.artist_name as composer, COUNT(*) as performances
FROM program_song_title pst
JOIN programs p ON pst.program_id = p.id
JOIN song_titles st ON pst.song_title_id = st.id
JOIN artists a ON st.composer_id = a.id
JOIN ensembles e ON pst.ensemble_id = e.id
WHERE p.event_date BETWEEN '2025-09-01' AND '2026-07-31'
  AND e.type != 'Unknown'
  AND st.composer_id IS NOT NULL
GROUP BY e.type, a.id, a.artist_name
ORDER BY e.type, performances DESC
```

---

## Analysis Notes (2025–26 Final — re-run 2026-07-09 after geo fix + late uploads)

- Final 2025–26 totals: **63 programs, 40 schools, 37 directors, 694 unique titles, 736 performances** — up from the 57/37/35/631/664 numbers originally baked into `docs/year_end_email_template.md` and `docs/marketing_content_2025-26.md`. Both docs (including the 3 chart Artifacts referenced from the marketing doc) were refreshed to match as of 2026-07-09. Re-verify before final send/publish in case more programs land — this dataset moved three times in one day.
- Run all queries via the `database-query` MCP tool (Steps 1–14 can be parallelized in groups)
- Compare results to the 2025–26 baseline email for year-over-year narrative
- Trier, Germany still has **2 schools** — international presence is holding, not a one-off. Keep watching for more.
- Step 2 (Geography) is clean — the null `geo_state`/`country` row from earlier is gone (Rick fixed the underlying school records). All 63 programs now resolve to 8 state/country rows: NJ 40 programs/29 schools, RI 7/1, WI 4/2, NY 4/2, PA 3/2, MD 2/1, Trier DE 2/2, TN 1/1. NJ's share of the dataset grew to ~63% (was ~58% at baseline) — worth naming explicitly if writing NJMEA-specific copy.
- "Time" (Jennifer Lucy Cook) is still the most-performed title overall (7 performances), 5 of them in spring — no longer *entirely* a spring piece as the baseline email claimed.
- Roger Emerson remains the dominant winter arranger (10 performances / 7 programs, winter-only), unchanged from the earlier snapshot and still ~2x the next arranger.
- **Composer ranking shifted**: Elaine Hagenberg (10) now leads outright; Ola Gjeilo (8) is second among *named* composers (behind only "Traditional," a placeholder attribution, and tied with Handel and Cook at 8). Kyle Pederson (5) has dropped out of the overall top tier — Eric Whitacre (6) and Andy Beck (6) took his place. If reusing "top four living composers" language, use Hagenberg, Gjeilo, Cook, Whitacre — not Pederson.
- "Traditional" still leads the overall composer list by raw count (11 performances) — keep excluding/footnoting it rather than treating it as a "top composer."
- The email template is in `docs/year_end_email_template.md`
