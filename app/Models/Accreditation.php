<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Accreditation extends Model
{
    protected $fillable = [
        'organization_id', 'application_id', 'verification_code',
        'status', 'active_org_marker', 'issued_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'expires_at' => 'date',
        ];
    }

    public static function generateVerificationCode(): string
    {
        do {
            $code = Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
        } while (self::where('verification_code', $code)->exists());

        return $code;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationModel::class, 'application_id');
    }
}
