<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A notice the Desk Office sent to organizations, and who it was meant for. */
class OfficeNotice extends Model
{
    public const AUDIENCES = [
        'all' => 'Every organization',
        'accredited' => 'Accredited organizations',
        'sector' => 'A sector',
        'barangay' => 'A barangay',
        'organization' => 'One organization',
    ];

    protected $fillable = ['sender_id', 'title', 'body', 'audience', 'audience_value', 'recipients', 'emailed'];

    protected function casts(): array
    {
        return ['emailed' => 'boolean'];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** The representatives' accounts an audience reaches: active ones, with an organization. */
    public static function recipients(string $audience, ?string $value): Builder
    {
        return User::query()
            ->where('role', 'cso_rep')->where('is_active', true)
            ->whereHas('organization', fn (Builder $q) => match ($audience) {
                'organization' => $q->whereKey((int) $value),
                'sector' => $q->where('sector', $value),
                'barangay' => $q->where('barangay', $value),
                'accredited' => $q->whereHas('accreditations', fn ($a) => $a->where('status', 'active')),
                default => $q,
            });
    }

    /**
     * Whether this notice's audience includes the representative's organization: the same rule as
     * recipients(), checked in PHP so a list of notices needs no query each. $accredited saves the
     * one lookup when the caller already knows.
     */
    public function reaches(Organization $organization, ?bool $accredited = null): bool
    {
        return match ($this->audience) {
            'organization' => (int) $this->audience_value === $organization->id,
            'sector' => $this->audience_value === $organization->sector,
            'barangay' => $this->audience_value === $organization->barangay,
            'accredited' => $accredited ?? $organization->isAccredited(),
            default => true,
        };
    }

    /** "KALIPI (Women's) sector", "Brgy. Baay", the organization's name... */
    public function audienceLabel(): string
    {
        return match ($this->audience) {
            'organization' => Organization::find($this->audience_value)?->name ?? 'One organization',
            'sector' => "{$this->audience_value} sector",
            'barangay' => "Brgy. {$this->audience_value}",
            default => self::AUDIENCES[$this->audience],
        };
    }
}
