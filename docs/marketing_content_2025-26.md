# 2025–26 Year-End Marketing Content (Draft — Pending Publish Decisions)

Compiled 2026-07-08 from the 2025–26 year-end programming trends analysis (`docs/year_end_analysis_workflow.md` / `docs/year_end_email_template.md`). All items below are drafted and ready for review; nothing has been published or sent externally. Newsletter #2 exists as an unsent draft in the app.

**Source numbers used throughout (updated 2026-07-09):** 63 concerts · 40 schools · 37 directors · 694 unique titles · 736 performances · Sep 2025–Jul 2026. Ola Gjeilo 8 performances/7 programs (Norway); Pärt Uusberg 3/3 (Estonia); Eriks Ešenvalds 3/3 (Latvia).

---

## 1. In-App Newsletter (draft, unsent)

- **Record:** `newsletters` table, id **2**, `sent_at` is null.
- **Subject/Headline:** "What Your Colleagues Programmed in 2025–26"
- **6 sections:** The Big Picture · Living Composers Are Having a Moment · The Nordic Sound Is Everywhere · The Most-Performed Piece of the Year · Winter vs. Spring: A Clear Divide · A Note on the Data
- **CTA:** "Upload a Program" → `https://choirtrends.com/add-program`
- **Bug fixed this session:** `resources/views/emails/newsletter.blade.php` line 28 — `cta_text` now renders with `{!! !!}` instead of `{{ }}`, so HTML (e.g. `<i>`) in the button text displays correctly instead of showing literal tags.
- **To review/send:** Founder > Newsletter in the app (loads this draft automatically since it's the latest unsent one). Use "Save & Preview Email" before "Send to All". CLI equivalent: `php artisan newsletter:send --id=2 --preview`.

---

## 2. Social Media Posts — hook: "Why Is Everyone Singing Gjeilo?"

### LinkedIn (professional audience)

> **Why is everyone singing Gjeilo?**
>
> We looked at 736 choral performances logged by directors this year — and Ola Gjeilo showed up in 7 different programs, more than almost any other living composer.
>
> He's not alone. Alongside Gjeilo (Norway), we're seeing real staying power from Pärt Uusberg (Estonia) and Eriks Ešenvalds (Latvia) — a distinct "Nordic/Baltic" sound built on spacious harmony and text-centered writing that's become one of the clearest trends in what directors are actually programming this season.
>
> A few other things the data showed:
> • 694 unique titles out of 736 performances — directors are barely repeating each other
> • The most-performed single piece: "Time" by Jennifer Lucy Cook, 7 performances
> • Living composers dominate the top 5 — history shows up mostly in December
>
> We track this kind of programming data at ChoirTrends so directors can see what their peers are choosing — not to copy, but to know what's actually resonating right now. Curious what's driving the Nordic sound's staying power? [link]
>
> #ChoralMusic #ChoirDirector #MusicEducation

### Facebook — ChoirTrends Page

> 🎵 **Why is everyone singing Gjeilo?**
>
> We crunched the numbers on this year's uploaded concert programs, and Ola Gjeilo showed up in 7 different schools' programs — more than almost any other composer we tracked. Add in Pärt Uusberg and Eriks Ešenvalds, and there's a clear "Nordic sound" moment happening in choir rooms right now.
>
> Some other fun finds from 736 total performances:
> ✅ 694 unique titles — almost nobody's programming is the same
> ✅ "Time" by Jennifer Lucy Cook was the most-performed piece this year
> ✅ Living composers dominate — outside of the holidays, directors are leaning modern
>
> Want to see what other directors are programming (and maybe find your next concert opener)? That's exactly what ChoirTrends is for. 👇 [link]

### Facebook — "I am a choir director" style groups (community-safe, no promo)

> Anyone else noticing how much Gjeilo is showing up on programs this year? 👀
>
> I've been looking at concert programs shared through ChoirTrends (a tool a few of us use to log our seasons), and Gjeilo, Uusberg, and Ešenvalds keep popping up together — that spacious, atmospheric "Nordic" sound seems to be everywhere right now, not just at the big-name schools.
>
> Curious if that matches what you all are seeing/singing this year, or if it's just what's crossing my feed. What's your go-to Nordic/Baltic piece?

**Open item:** confirm each target group's self-promotion rules before posting — the group version deliberately soft-pedals the ChoirTrends mention.

---

## 3. Chart Graphics (Artifacts — hosted on claude.ai)

| Version | Size | URL | Notes |
|---|---|---|---|
| Facebook / general feed | 1080×1080 (square) | https://claude.ai/code/artifact/642516a7-9987-438e-b4a9-57e4451379f8 | Has `choirtrends.com` CTA pill |
| LinkedIn | 1200×630 (landscape) | https://claude.ai/code/artifact/c0d29418-5b1e-42be-a594-12ccf503b97b | Two-column layout, has CTA pill |
| Facebook group (no CTA) | 1080×1080 (square) | https://claude.ai/code/artifact/6f228af1-a658-4ca3-a972-527cfba38b67 | CTA pill removed, kicker text neutral (no brand color) |

All three: bar chart of Gjeilo (8 performances/7 programs) vs. Uusberg and Ešenvalds (3/3 each), footer citing 736 performances / 63 programs / Sep 2025–Jul 2026. Widen the browser before screenshotting for full resolution. Redeployed 2026-07-09 to match final data (same URLs, no link changes needed).

---

## 4. Choral Journal Pitch + Article (long-form)

**Print/visual version:** `docs/choral_journal_article_draft.pdf` — the shared article body + methodology sidebar below, laid out as a letter-size magazine-style PDF (serif body text, sidebar floated beside "A note on the data"). This one draft is reused as-is for all three outlets (Choral Journal, Chorus America, NJMEA Tempo) per Rick's 2026-07-08 decision — only the pitch notes differ per outlet.

### Pitch note (query email)

> **Subject:** Pitch: What 736 Logged Performances Revealed About Repertoire Trends — Data Feature for Choral Journal
>
> Dear Choral Journal Editors,
>
> I'd like to propose a data-driven feature on choral programming trends, drawn from concert programs logged by directors on ChoirTrends, a repertoire-tracking platform I founded. Over the 2025–26 school year, directors uploaded 63 concert programs spanning 40 schools — enough to surface real patterns in what's actually being programmed, from the dominance of living composers to a distinct "Nordic/Baltic" stylistic thread running through both winter and spring concerts.
>
> I want to be upfront about scope: this is not a scientifically representative national sample — it reflects early adopters of one platform, concentrated in the Northeast. I think that's still useful to your readers as an early signal, and I've written the piece to be transparent about that limitation rather than oversell it. The draft is attached, roughly 1,300 words, with room to adjust length or add a data sidebar/table if useful for layout. Happy to update the analysis with a larger dataset for a later issue if this one is too early-stage for you.
>
> Let me know if this is a fit, and if there's a preferred format or submission process I should follow.
>
> Thank you for your consideration,
> Rick Retzko
> Founder, ChoirTrends.com & TheDirectorsRoom.com
> rick@mfrholdings.com

### Article

> ### What 736 Performances Reveal About Choral Programming in 2025–26
>
> *A first look at repertoire trends from a season of shared concert programs*
>
> By Rick Retzko
>
> Choral directors make dozens of programming decisions at least twice a year decisions — how to open the next concert, which arranger's "Veni, Veni" to use (Trotta!), whether this is finally the year to program that Ešenvalds octavo sitting in the folder. Multiply those decisions across even a few dozen programs and certain patterns begin to emerge: e.g. which composers are being sung, not just anthologized; which arrangers quietly dominate a season; and whether the field is moving in any specific direction.
>
> This article is a first attempt to look at those patterns directly, using data from ChoirTrends.com, a platform where directors upload their concert programs — repertoire, ensembles, dates, and schools — as they build each season. From September 2025 through July 2026, Choral Directors logged 63 concert programs from 40 schools, totaling 736 individual performances of 694 unique titles.
>
> **A note on the data, up front.** This is a small, self-selected sample — early users of one platform, concentrated in New Jersey (40 of 63 programs), with smaller representation from Rhode Island, New York, Pennsylvania, Wisconsin, Maryland, and Tennessee, plus a first international data point from two schools in Trier, Germany. This was not a scientific survey of American choral programming but instead a real, unfiltered look at actual concert programs as directors shared them — no anthologizing, no self-reporting bias toward "important" repertoire, just what actually was performed. As the dataset grows in future seasons, we expect these patterns to sharpen; for now, treat what follows as a signal worth watching rather than a settled conclusion.
>
> **Living composers, not historical ones, dominate the season.** Outside the December repertoire, the most-programmed composers were overwhelmingly living composers working today: Elaine Hagenberg, Ola Gjeilo, Jennifer Lucy Cook, and Eric Whitacre all appeared in the season's top tier, all composing from within the last decade. The only composer from an earlier era to break into the top five — George Frideric Handel — owed his position almost entirely to holiday programming. This tracks with a broader shift many directors have felt anecdotally: that today's active repertoire pipeline is increasingly built around living voices, not the historical canon, except where the calendar itself calls for tradition.
>
> **A distinct Nordic/Baltic sound runs through the season.** One of the clearest stylistic threads in the data is a cluster of composers writing spacious, atmospheric, text-centered choral music from the Nordic and Baltic countries: Ola Gjeilo (Norway), Pärt Uusberg (Estonia), and Eriks Ešenvalds (Latvia). Gjeilo alone was performed 8 times across 7 different programs — trailing only Elaine Hagenberg and generic "Traditional/anonymous" attributions among all composers in the dataset — with "Tundra" appearing three times on its own. Uusberg and Ešenvalds each appeared 3 times across 3 programs, clearing the "trend" threshold on a much smaller total sample size than Gjeilo. Taken together, this Nordic/Baltic aesthetic was present in roughly one in ten logged programs — notable given that none of these composers write primarily in English or draw on American choral tradition. It's a pattern consistent with what several state ACDA reading sessions have highlighted in recent years, which translates into actual programming, not just conference buzz.
>
> **One piece stood out above the rest.** "Time," by Jennifer Lucy Cook, was the most-performed single title in the dataset — 7 performances, all but two of them in the spring semester. No other title came close during spring; the next tier ("Tundra" by Ola Gjeilo, "Glow" by Eric Whitacre, "Veni, Veni Emmanuel" by Michael John Trotta, "Muusika" by Pärt Uusberg, and "O Love" by Elaine Hagenberg) each landed 3 performances across the full year. What's more striking than any single title, though, is how rare repeats were at all: of 736 total selections, only 42 included a title performed more than once anywhere in the dataset. Directors, at least in this sample, are not reaching for the same shelf.
>
> **Winter and spring tell different stories.** Splitting the season in two reveals a clear seasonal divide. Winter programming (September through January, 24 concerts) leaned sacred and seasonal — Handel, Byrd, Victoria, and Vivaldi appeared alongside Gjeilo's wintry soundscapes and Trotta's "Veni, Veni Emmanuel." On the arranging side, Roger Emerson was the dominant voice of the winter season by a wide margin: 10 performances across 7 programs, nearly double the next most-used arranger. Spring programming (February through July, 39 concerts) shifted decisively toward contemporary American voices — Hagenberg, Cook, Pederson, Rollo Dilworth, and Marta Keen led the composer list, while the arranger landscape broadened considerably, with Mark Brymer, Kirby Shaw, Craig Hella Johnson, and Audrey Snyder all posting strong numbers. Pop crossover repertoire also surfaced in spring with arrangements of "Dust in the Wind" and "Homeward Bound" appearing multiple times. This suggests some directors are deliberately bridging choral and popular repertoire for spring audiences in a way that doesn't show up in winter programming at all.
>
> **What we don't yet know.** Ensemble-level detail — whether a piece was sung by an SATB choir, an SSA/treble ensemble, a TTBB group, or performed a cappella — was only reliably recorded for about 23% of performances in this dataset; the rest are logged simply as "Unknown" ensemble type. Where we do have that detail, some patterns emerge (SATB choirs accounted for the largest typed share, and a cappella performances were concentrated there as well), but with this much missing data, we're not treating the ensemble-type breakdown as reliable yet — it's included for context, not as a finding.
>
> **Where this goes next.** This is a first snapshot from a single season and a still-small, geographically concentrated dataset. The value of this kind of analysis compounds with scale: as more directors upload more programs, patterns like the Nordic/Baltic thread, the winter/spring arranger divide, and shifts in the historical-versus-contemporary balance will become sharper and more defensible. We'll be revisiting this analysis with a hopefully larger, more geographically diverse sample as we welcome any Chorl Director's data to be included as part of the bigger picture.

> **What else will you find?** While this article focuses on a single season of submissions, ChoirTrends.com now contains more than 20 years of archived concert programs from New Jersey All-State Mixed Chorus.  As the database continues to grow, it will offer an increasingly valuable resource for studying repertoire programming, historical trends, and evolving practices in choral music eduation.  If you are the keeper of the same archival programs for your State, please consider adding those programs to ChoirTrends.com.
> ---
>
> Rick Retzko is the founder of ChoirTrends.com and TheDirectorsRoom.com. Rick can be reached at rick@mfrholdings.com.
>
> ChoirTrends.com is a free, online resource offered as a gift to the choral community, requiring the commitment to upload at least two choral programs per year.
>
> TheDirectorsRoom.com streamlines the administration of auditioned regional and state honors choirs with integrated registration and online adjudication.

### Methodology sidebar (kept separate from body, for editor/fact-check use)

> Data drawn from ChoirTrends concert program logs, September 1, 2025–July 31, 2026. Figures include only performances with a matching program and event date in range; composer/arranger figures require an attributed composer or arranger on the song title record. "Trend" threshold set at 3+ performances per the platform's standard reporting convention. Full query detail available on request.

---

## 5. Chorus America Pitch Note

Reuses the same article body and methodology sidebar as the Choral Journal draft above (Section 4) — only this pitch note changes.

> **Subject:** Pitch: A Repertoire Trend Feature for Chorus America — Living Composers, the Nordic Sound, and What's Actually Being Programmed
>
> Dear Chorus America Editors,
>
> I'd like to propose a short data feature on choral repertoire trends for your readers, drawn from concert programs logged on ChoirTrends, a repertoire-tracking platform I founded. Over the 2025–26 school year, directors logged 63 concert programs across 40 schools, and a few patterns stood out that I think will resonate beyond the scholastic world your Chorus America membership sits alongside: living composers dominating programming outside the holiday season, and a distinct "Nordic/Baltic" aesthetic — Ola Gjeilo, Pärt Uusberg, Eriks Ešenvalds — showing up across roughly one in ten programs.
>
> I want to be transparent about the dataset's current shape: this season's sample is drawn primarily from scholastic choral programs, not yet from the community and professional choruses that make up the core of Chorus America's membership. I think the repertoire trends still cross over — programming decisions in school choirs are often a leading indicator of what's moving through community and professional ensembles a season or two later — but I wanted to name that limitation rather than overstate the fit. If there's interest, I'd welcome the chance to expand this analysis with community/professional chorus data specifically for a future piece.
>
> The draft is attached, roughly 1,300 words with a methodology sidebar, and flexible on length or format. Let me know if this fits your editorial calendar and what your submission process looks like.
>
> Thank you for your consideration,
> Rick Retzko
> Founder, ChoirTrends.com & TheDirectorsRoom.com
> rick.retzko@gmail.com

---

## 6. NJMEA Tempo Pitch Note

Also reuses the same article body and methodology sidebar as Section 4.

> **Subject:** Pitch for Tempo: What New Jersey Choral Programs Reveal About Repertoire Trends
>
>
> Rick Retzko
> Founder, ChoirTrends.com & TheDirectorsRoom.com
> rick.retzko@gmail.com

---

## 7. Open Decisions for Next Week

- [ ] Newsletter #2: review, preview-send to self, decide send date/whether to send at all
- [ ] Confirm Facebook group posting/self-promo rules before posting the group version
- [ ] Finalize the actual `[link]` URL to drop into the LinkedIn/FB Page posts (product page vs. this specific finding's landing page)
- [ ] Confirm submission process/lead time with ACDA (Choral Journal), Chorus America, and NJMEA (Tempo) — all three pitch notes are drafted and ready to send
- [ ] Decide whether the article body needs per-outlet tailoring (e.g., leading with the NJ concentration for Tempo, or the scholastic-vs-community caveat for Chorus America) rather than reusing one draft across all three
- [ ] Decide on byline consistency (`Founder, ChoirTrends.com & TheDirectorsRoom.com`) across all channels, including the social posts above which currently don't carry a byline
