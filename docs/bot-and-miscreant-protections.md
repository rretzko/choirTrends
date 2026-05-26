# Bot & Miscreant Protections

## Current Protections

### 1. IP-Based Rate Limiting
**File:** `app/Actions/Fortify/CreateNewUser.php`

Registration attempts are throttled per IP address. A maximum of **3 attempts** are allowed within a **1-hour** window. On breach, a `ValidationException` is thrown with a user-facing message including the seconds remaining.

```php
$throttleKey = 'register|'.request()->ip();
RateLimiter::hit($throttleKey, 3600); // 1-hour decay
```

---

### 2. Full Name Requirement
**File:** `app/Actions/Fortify/CreateNewUser.php`

The `name` field must contain at least two whitespace-separated words (first + last name). Single-word or empty-word submissions are rejected.

```php
if (! preg_match('/\S+\s+\S+/', $trimmed)) { ... }
```

**Known gap:** Does not block common fake names such as "No Thanks", "Test User", or "Asdf Asdf". See [Next Steps](#next-steps).

---

### 3. Name Character Validation
**File:** `app/Actions/Fortify/CreateNewUser.php`

Name fields may only contain Unicode letters, spaces, hyphens, and apostrophes. Numeric or symbol-heavy names are rejected.

```php
if (! preg_match('/^[\p{L}\s\'\-\.]+$/u', $trimmed)) { ... }
```

---

### 4. Numeric-Run Email Detection
**File:** `app/Actions/Fortify/CreateNewUser.php`

Email addresses whose local part contains 6 or more consecutive digits are rejected. Targets machine-generated addresses like `nothanks849278194@gmail.com`. Applies to all email domains.

```php
if (preg_match('/\d{6,}/', $local)) { ... }
```

---

### 5. Gmail Dot-Trick Normalization
**File:** `app/Actions/Fortify/CreateNewUser.php`

For `@gmail.com` and `@googlemail.com` addresses, the local part is normalized by stripping dots before checking for duplicates against existing verified accounts. This prevents `j.o.h.n@gmail.com` from bypassing a block on `john@gmail.com`.

```php
$normalized = str_replace('.', '', $local);
```

---

### 6. Suspicious Segmented Gmail Detection
**File:** `app/Actions/Fortify/CreateNewUser.php`

Gmail addresses whose local part contains 4+ dot-separated segments where ≥60% of segments are ≤2 characters are rejected. Targets machine-generated addresses like `a.b.c.d@gmail.com`.

```php
if (count($segments) >= 4) {
    $shortCount = count(array_filter($segments, fn ($s) => strlen($s) <= 2));
    if ($shortCount / count($segments) >= 0.6) { ... }
}
```

---

### 7. Viewport Hidden Field
**File:** `app/Actions/Fortify/CreateNewUser.php`

A `viewport` field populated by JavaScript (`{width}x{height}`) is required and must match the pattern `\d+x\d+`. Bots that submit forms without executing JavaScript will fail this check. The error message is deliberately generic to avoid tipping off attackers.

```php
'viewport' => ['required', 'regex:/^\d+x\d+$/'],
```

---

## Next Steps

These improvements are recommended if bot activity continues or escalates.

### A. Name Blocklist
Add a validation check rejecting known fake/rejection names ("No Thanks", "Test User", "Asdf Asdf", etc.). Low effort. Addresses the gap in protection #2.

### B. Cloudflare Turnstile (or hCaptcha)
An invisible bot challenge on the registration form. The single highest-impact addition available — nearly impossible for bots to pass, zero friction for real users, free tier available. Recommended if bot registrations persist after the current protections.

---

## Incident Log

### 2026-05-26 — Dual Bot Registration
Two accounts registered with the same name ("No Thanks") from the same IP (`209.242.187.198`) at a 73-second interval, using different emails (`no@gmail.com`, `nothanks849278194@gmail.com`). No harm was done — email verification gated all further access. The 73-second gap was deliberate: the bot waited for the then-60-second rate limit window to expire before the second attempt.

**Protections added in response:** rate limit window extended to 1 hour (#1); numeric-run email detection added (#4).
