<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }
}
