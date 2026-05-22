# Survey Invitation Workflow

## Overview

The survey invitation system targets registered users who have not uploaded any programs, sending up to 3 drip emails asking for feedback on why they haven't engaged. Responses are stored and forwarded to the founder.

---

## Relevant Files

| File | Role |
|---|---|
| `app/Console/Commands/SendSurveyEmails.php` | Artisan command — finds eligible users, queues emails |
| `app/Mail/SurveyInvitation.php` | Mailable — builds the signed URL and passes data to the template |
| `resources/views/emails/survey-invitation.blade.php` | Plain-HTML email template |
| `app/Livewire/Survey/Show.php` | Livewire component — renders and handles the survey form |
| `app/Models/SurveyResponse.php` | Eloquent model — stores submitted responses |
| `app/Mail/SurveyResponseReceived.php` | Mailable — notifies the founder when a response is submitted |
| `app/Enums/SurveyReason.php` | Enum — the available reasons a user can select |
| `tests/Feature/Commands/SendSurveyEmailsTest.php` | Feature tests for the command |
| `tests/Feature/Survey/ShowTest.php` | Feature tests for the survey page |

---

## Step-by-Step Flow

### 1. Trigger — `php artisan survey:send`

`SendSurveyEmails` command queries for **eligible users** — all four conditions must be true:

- Registered **≥ 2 weeks ago**
- `survey_emails_sent_count < 3` (hard cap at 3 total emails)
- Has **no programs** (`doesntHave('programs')`)
- Has **never submitted a survey response** (`doesntHave('surveyResponses')`)

For each match it queues a `SurveyInvitation` mailable and immediately increments `survey_emails_sent_count`.

---

### 2. The Email — `SurveyInvitation` Mailable

`app/Mail/SurveyInvitation.php` builds the message:

- **From:** `config('mail.from.address')` aliased as "Rick Retzko, Founder"
- **Survey URL:** a **signed route** (`URL::signedRoute('survey.show', ...)`) — tamper-proof, tied to the user's ID
- **Remaining count:** `3 - survey_emails_sent_count` passed to the template so the footer message stays accurate

---

### 3. The Template — `survey-invitation.blade.php`

A plain-HTML email (no Blade components, intentionally simple for email client compatibility) that renders:

- Personalized greeting with `$user->name`
- A teal CTA button → the signed survey URL
- Footer: "you'll receive at most **N** more reminders" (using `$remainingEmails`)
- A fallback plain-text web address for copy/paste

---

### 4. The Survey Page — `Survey\Show` Livewire Component

Route: `GET survey/{user}` with `->middleware('signed')` — the signed URL is validated before the page loads.

On `mount()` it checks whether the user **already responded** (`alreadyResponded` flag). If not, the form is shown. The user selects from `SurveyReason` enum cases (checkboxes), optionally fills in an "other reason" and freeform comments.

On `submit()`:
1. Validates that at least one reason is selected; if `Other` is chosen, `otherReason` must be filled
2. Creates a `SurveyResponse` record with `reasons` (array), `other_reason`, `comments`
3. Queues a `SurveyResponseReceived` mailable to `config('app.founder')` (founder email)
4. Sets `submitted = true` to show a confirmation state

---

## Key Guard Rails

| Guard | Where enforced |
|---|---|
| Max 3 emails | `survey_emails_sent_count < 3` in command query |
| Already responded | `doesntHave('surveyResponses')` in command query + `alreadyResponded` flag in component |
| Has programs | `doesntHave('programs')` in command query |
| Signed URL | `->middleware('signed')` on route |

---

## Known Issue

The `$skipped` counter in `SendSurveyEmails::handle()` is declared and reported but never incremented — it will always output `0`.
