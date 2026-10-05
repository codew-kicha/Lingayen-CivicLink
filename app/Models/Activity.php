<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activity extends Model
{
    use HasFactory;

    /**
     * Who initiated the activity: the organization on its own, or the LGU with the organization
     * taking part. This is what separates advocacy from attendance in the analytics.
     */
    public const SOURCES = [
        'independent' => 'Self-initiated',
        'lgu_organized' => 'LGU-organized',
    ];

    protected $fillable = [
        'organization_id', 'logged_by', 'verified_by', 'title', 'description',
        'activity_date', 'participants_estimate', 'activity_source', 'status', 'verified_at', 'rejection_reason',
    ];

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->activity_source] ?? 'Not recorded';
    }

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'verified_at' => 'datetime',
        ];
    }

    /** Activities an organization logged itself or is tagged on as a partner. */
    public function scopeCrediting(Builder $query, Organization $organization): void
    {
        $query->where(fn ($q) => $q->where('organization_id', $organization->id)
            ->orWhereHas('partnerOrganizations', fn ($p) => $p->where('organizations.id', $organization->id)));
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function loggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function partnerOrganizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'activity_organization');
    }
}
