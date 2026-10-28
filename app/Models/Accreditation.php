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

    /**
     * Marks every active accreditation whose expiry date has passed as expired, so the organization
     * leaves the public directory and must renew before logging activities. Shared hosting has no
     * scheduler, so the ExpireLapsedAccreditations middleware runs this once a day on the first visit.
     */
    public static function expireLapsed(): int
    {
        $lapsed = self::where('status', 'active')->whereDate('expires_at', '<', now()->toDateString())->get();

        foreach ($lapsed as $accreditation) {
            $accreditation->update(['status' => 'expired', 'active_org_marker' => null]);
            // Recorded as the system (no actor), not as whoever made the day's first visit.
            AuditLog::create([
                'actor_id' => null,
                'action' => 'accreditation.expired',
                'subject_type' => 'Organization',
                'subject_id' => $accreditation->organization_id,
                'meta' => ['verification_code' => $accreditation->verification_code, 'expired_on' => $accreditation->expires_at->toDateString()],
            ]);
        }

        return $lapsed->count();
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
