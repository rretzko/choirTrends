<?php

namespace App\Actions\Fortify;

use App\Enums\ReferralSource;
use App\Models\User;
use App\Services\TurnstileVerificationService;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Consonant pairs that occur naturally in names across common languages.
     *
     * @var list<string>
     */
    private const array COMMON_CONSONANT_PAIRS = [
        'bb', 'bl', 'br', 'bs', 'ch', 'ck', 'cl', 'cr', 'ct', 'cz', 'dd', 'dg', 'dh', 'dl', 'dm', 'dn', 'dr', 'ds',
        'dt', 'dw', 'dz', 'ff', 'fl', 'fr', 'fs', 'ft', 'gg', 'gh', 'gl', 'gm', 'gn', 'gr', 'gs', 'gt', 'hl', 'hm',
        'hn', 'hr', 'hs', 'ht', 'hw', 'jk', 'kh', 'kk', 'kl', 'km', 'kn', 'kr', 'ks', 'kt', 'kw', 'ld', 'lf', 'lg',
        'lh', 'lk', 'll', 'lm', 'ln', 'lp', 'lr', 'ls', 'lt', 'lv', 'lw', 'lz', 'mb', 'mc', 'mm', 'mn', 'mp', 'ms',
        'mz', 'nc', 'nd', 'nf', 'ng', 'nh', 'nj', 'nk', 'nl', 'nm', 'nn', 'np', 'nq', 'ns', 'nt', 'nv', 'nw', 'nx',
        'nz', 'ph', 'pl', 'pp', 'pr', 'ps', 'pt', 'rb', 'rc', 'rd', 'rf', 'rg', 'rh', 'rj', 'rk', 'rl', 'rm', 'rn',
        'rp', 'rq', 'rr', 'rs', 'rt', 'rv', 'rw', 'rz', 'sb', 'sc', 'sd', 'sf', 'sg', 'sh', 'sk', 'sl', 'sm', 'sn',
        'sp', 'sq', 'ss', 'st', 'sv', 'sw', 'sz', 'tc', 'tf', 'th', 'tl', 'tm', 'tn', 'tr', 'ts', 'tt', 'tw', 'tz',
        'vl', 'vn', 'vr', 'vs', 'wb', 'wd', 'wf', 'wh', 'wk', 'wl', 'wm', 'wn', 'wr', 'ws', 'wt', 'wz', 'xt', 'zb',
        'zd', 'zh', 'zk', 'zl', 'zm', 'zn', 'zr', 'zs', 'zw', 'zz',
    ];

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $throttleKey = 'register|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => [__('Too many registration attempts. Please try again in :seconds seconds.', ['seconds' => $seconds])],
            ]);
        }

        RateLimiter::hit($throttleKey, 3600);

        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $trimmed = trim((string) $value);
                    if (! preg_match('/\S+\s+\S+/', $trimmed)) {
                        $fail(__('Please enter your full name (first and last name required).'));

                        return;
                    }
                    if (! preg_match('/^[\p{L}\s\'\-\.]+$/u', $trimmed)) {
                        $fail(__('The name may only contain letters, spaces, hyphens, and apostrophes.'));

                        return;
                    }
                    if ($this->looksLikeGibberish($trimmed)) {
                        $fail(__('Please enter your real first and last name.'));
                    }
                },
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $email = strtolower(trim((string) $value));
                    $local = explode('@', $email)[0];

                    if (preg_match('/\d{6,}/', $local)) {
                        $fail(__('Registration failed. Please try again.'));

                        return;
                    }

                    if (! str_ends_with($email, '@gmail.com') && ! str_ends_with($email, '@googlemail.com')) {
                        return;
                    }

                    $segments = explode('.', $local);

                    if (count($segments) >= 4) {
                        $shortCount = count(array_filter($segments, fn (string $s): bool => strlen($s) <= 2));
                        if ($shortCount / count($segments) >= 0.6) {
                            $fail(__('Registration failed. Please try again.'));

                            return;
                        }
                    }

                    $normalized = str_replace('.', '', $local);
                    $exists = User::query()
                        ->whereRaw("REPLACE(LOWER(SUBSTR(email, 1, INSTR(email, '@') - 1)), '.', '') = ?", [$normalized])
                        ->where(function ($q): void {
                            $q->where('email', 'like', '%@gmail.com')
                                ->orWhere('email', 'like', '%@googlemail.com');
                        })
                        ->whereNotNull('email_verified_at')
                        ->exists();

                    if ($exists) {
                        $fail(__('An account with this email address already exists.'));
                    }
                },
            ],
            'password' => $this->passwordRules(),
            'referral_source' => ['nullable', Rule::enum(ReferralSource::class)],
            'referral_detail' => ['nullable', 'string', 'max:255'],
            'viewport' => ['required', 'regex:/^\d+x\d+$/'],
            'cf-turnstile-response' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $token = is_string($value) ? $value : null;

                    if (! app(TurnstileVerificationService::class)->verify($token, request()->ip())) {
                        $fail(__('Please complete the verification check and try again.'));
                    }
                },
            ],
        ], [
            'viewport.required' => __('Registration failed. Please try again.'),
            'viewport.regex' => __('Registration failed. Please try again.'),
            'cf-turnstile-response.required' => __('Please complete the verification check and try again.'),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'referral_source' => $input['referral_source'] ?? null,
            'referral_detail' => $input['referral_detail'] ?? null,
        ]);
    }

    /**
     * Detect keyboard-mash names (e.g. "Bnbvqzqn Mgldnj") by counting consonant pairs
     * that rarely appear in real names. Real users in production score 0–1; bots score 3+.
     */
    private function looksLikeGibberish(string $name): bool
    {
        $ascii = Str::lower(Str::ascii($name));
        $uncommonPairCount = 0;

        foreach (preg_split('/[^a-z]+/', $ascii, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $word) {
            for ($i = 0; $i < strlen($word) - 1; $i++) {
                $pair = substr($word, $i, 2);

                if (preg_match('/^[bcdfghjklmnpqrstvwxz]{2}$/', $pair) && ! in_array($pair, self::COMMON_CONSONANT_PAIRS, true)) {
                    $uncommonPairCount++;
                }
            }
        }

        return $uncommonPairCount >= 3;
    }
}
