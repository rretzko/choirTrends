<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property-read Collection<int, UserLogin> $userLogins
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
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

            if ($founderEmail) {
                Mail::to($founderEmail)->send(new \App\Mail\NewUserRegistered($user));
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

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class);
    }

    public function privacy(): HasOne
    {
        return $this->hasOne(UserPrivacy::class);
    }

    public function userLogins(): HasMany
    {
        return $this->hasMany(UserLogin::class);
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
