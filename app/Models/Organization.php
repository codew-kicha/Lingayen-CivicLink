<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'sector', 'barangay', 'barangay_psgc_code',
        'advocacy', 'org_chart', 'logo_path', 'public_visibility',
    ];

    protected function casts(): array
    {
        return [
            'public_visibility' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ApplicationModel::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class);
    }

    public function activeAccreditation(): HasMany
    {
        return $this->accreditations()->where('status', 'active');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function partneredActivities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_organization');
    }

    public function performanceScores(): HasMany
    {
        return $this->hasMany(PerformanceScore::class);
    }

    public function newsPosts(): BelongsToMany
    {
        return $this->belongsToMany(NewsPost::class, 'news_post_organization');
    }
}
