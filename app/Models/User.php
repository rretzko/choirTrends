<?php

namespace App\Models;

use App\Enums\ReferralSource;
use App\Enums\UserRole;
use App\Mail\NewUserRegistered;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property-read Collection<int, Program> $programs
 * @property-read UserPrivacy|null $privacy
 * @property-read Collection<int, QuickTip> $quickTips
 * @property-read Collection<int, School> $schools
 * @property-read Collection<int, SurveyResponse> $surveyResponses
 * @property-read Collection<int, UserLogin> $userLogins
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('name')) {
                $user->alpha_name = $user->generateAlphaName();
            }
        });

        static::created(function (User $user) {
            $founderEmail = config('app.founder');

            if ($founderEmail && ! $user->isAssistant()) {
                Mail::to($founderEmail)->send(new NewUserRegistered($user));
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'alpha_name',
        'email',
        'password',
        'welcomed_at',
        'onboarding_dismissed_at',
        'orientation_email_sent_at',
        'catalog_token',
        'catalog_enabled_at',
        'quick_tip_emails_enabled',
        'survey_emails_sent_count',
        'referral_source',
        'referral_detail',
        'role',
        'parent_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'welcomed_at' => 'datetime',
            'onboarding_dismissed_at' => 'datetime',
            'orientation_email_sent_at' => 'datetime',
            'catalog_enabled_at' => 'datetime',
            'quick_tip_emails_enabled' => 'boolean',
            'survey_emails_sent_count' => 'integer',
            'referral_source' => ReferralSource::class,
            'role' => UserRole::class,
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /** @return BelongsTo<User, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    /** @return HasOne<User, $this> */
    public function assistant(): HasOne
    {
        return $this->hasOne(User::class, 'parent_user_id');
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class);
    }

    public function privacy(): HasOne
    {
        return $this->hasOne(UserPrivacy::class);
    }

    public function surveyResponses(): HasMany
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function userLogins(): HasMany
    {
        return $this->hasMany(UserLogin::class);
    }

    public function songLyrics(): HasMany
    {
        return $this->hasMany(UserSongLyrics::class);
    }

    public function songFiles(): HasMany
    {
        return $this->hasMany(UserSongFile::class);
    }

    public function quickTips(): BelongsToMany
    {
        return $this->belongsToMany(QuickTip::class)
            ->withPivot('emailed_at', 'viewed_at')
            ->withCasts(['emailed_at' => 'datetime', 'viewed_at' => 'datetime']);
    }

    public function isFounder(): bool
    {
        $founderEmail = config('app.founder');

        return $founderEmail && $this->email === $founderEmail;
    }

    /** Temporary: gates Digital Programs beta access by email list (DIGITAL_PROGRAMS_EMAILS). */
    public function canAccessDigitalPrograms(): bool
    {
        if ($this->isAssistant()) {
            return $this->parent?->canAccessDigitalPrograms() ?? false;
        }

        if ($this->isFounder()) {
            return true;
        }

        $allowed = config('app.digital_programs_emails', []);

        return ! empty($allowed) && in_array($this->email, $allowed, true);
    }

    public function isAssistant(): bool
    {
        return $this->role === UserRole::Assistant;
    }

    /** The user whose digital programs this account manages (itself, unless this is an assistant account). */
    public function digitalProgramsOwner(): self
    {
        return $this->parent ?? $this;
    }

    public function digitalProgramsOwnerId(): int
    {
        return $this->parent_user_id ?? $this->id;
    }

    /**
     * Generate "Last, First" format from the user's name.
     */
    public function generateAlphaName(): string
    {
        $parts = explode(' ', trim($this->name ?? ''));

        if (count($parts) <= 1) {
            return $this->name ?? '';
        }

        $lastName = array_pop($parts);

        return $lastName.', '.implode(' ', $parts);
    }

    public function isCatalogEnabled(): bool
    {
        return $this->catalog_enabled_at !== null;
    }

    public function enableCatalog(): void
    {
        if (! $this->catalog_token) {
            $this->catalog_token = Str::uuid()->toString();
        }

        $this->catalog_enabled_at = now();
        $this->save();
    }

    public function disableCatalog(): void
    {
        $this->catalog_enabled_at = null;
        $this->save();
    }

    public function regenerateCatalogToken(): void
    {
        $this->catalog_token = Str::uuid()->toString();
        $this->save();
    }
}
