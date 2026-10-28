<?php

namespace App\Models;

use App\Notifications\VerifyEmailCode;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** How long an emailed confirmation code works, and how many wrong tries it allows. */
    public const EMAIL_CODE_MINUTES = 15;

    public const EMAIL_CODE_ATTEMPTS = 5;

    /**
     * Email confirmation by code instead of Laravel's link: a fresh 6-digit code replaces any
     * earlier one, and only its hash is stored. Called on registration and by "Send a new code".
     */
    public function sendEmailVerificationNotification(): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_code_hash' => Hash::make($code),
            'email_code_expires_at' => now()->addMinutes(self::EMAIL_CODE_MINUTES),
            'email_code_attempts' => 0,
        ])->save();

        $this->notify(new VerifyEmailCode($code));
    }

    /**
     * Checks a typed code. Returns null when it matched (the email is now confirmed), otherwise
     * the reason to show: no code, expired, too many tries, or wrong (with the tries left).
     */
    public function confirmEmailWithCode(string $code): ?string
    {
        if (! $this->email_code_hash) {
            return 'There is no code waiting for this account. Send a new one.';
        }
        if ($this->email_code_expires_at?->isPast()) {
            return 'This code has expired. Send a new one.';
        }
        if ($this->email_code_attempts >= self::EMAIL_CODE_ATTEMPTS) {
            return 'Too many wrong codes. Send a new one.';
        }

        if (! Hash::check($code, $this->email_code_hash)) {
            $this->forceFill(['email_code_attempts' => $this->email_code_attempts + 1])->save();
            $left = self::EMAIL_CODE_ATTEMPTS - $this->email_code_attempts;

            return $left > 0
                ? "That code is not right. {$left} ".str('try')->plural($left).' left.'
                : 'Too many wrong codes. Send a new one.';
        }

        $this->forceFill(['email_code_hash' => null, 'email_code_expires_at' => null, 'email_code_attempts' => 0]);
        $this->markEmailAsVerified();

        return null;
    }

    // Philippine mobile numbers in any common format (0917 123 4567, +63-917-...) become +639XXXXXXXXX.
    // Anything else is returned unchanged so validation can reject it.
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/[\s\-()]/', '', $phone);

        return preg_match('/^(?:\+?63|0)(9\d{9})$/', $digits, $m) ? '+63'.$m[1] : $phone;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCsoRep(): bool
    {
        return $this->role === 'cso_rep';
    }

    public function organization(): HasOne
    {
        return $this->hasOne(Organization::class);
    }

    public function newsPosts(): HasMany
    {
        return $this->hasMany(NewsPost::class, 'author_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    // Mirrors the column default so a just-created model isn't treated as deactivated.
    protected $attributes = ['is_active' => true];

    // Account state (is_active, deactivation, login tracking) is deliberately not
    // mass-assignable: only the account-management code may change it.
    protected $fillable = [
        'name',
        'position',
        'email',
        'phone',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_code_hash',
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
            'email_code_expires_at' => 'datetime',
            'privacy_consented_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }
}
