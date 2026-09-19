<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id', 'logged_by', 'verified_by', 'title', 'description',
        'activity_date', 'participants_estimate', 'status', 'verified_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'verified_at' => 'datetime',
        ];
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
