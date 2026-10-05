<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class Organization extends Model
{
    use HasFactory;

    /** An accredited organization with no verified activity in this long is flagged inactive (PRD §3). */
    public const INACTIVE_AFTER_MONTHS = 6;

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

    protected static function booted(): void
    {
        // Forms submit the barangay name; the PSGC code is derived so the two never disagree.
        static::saving(function (Organization $organization) {
            $organization->barangay_psgc_code = array_search($organization->barangay, config('barangays'), true) ?: null;
        });
    }

    /**
     * Accredited for at least the inactivity window, yet with no verified activity in it, either
     * logged by the organization or crediting it as a partner.
     */
    public function scopeInactive(Builder $query): void
    {
        $cutoff = now()->subMonths(self::INACTIVE_AFTER_MONTHS)->toDateString();
        $recent = fn ($q) => $q->where('status', 'verified')->where('activity_date', '>=', $cutoff);

        $query->whereHas('accreditations', fn ($q) => $q->where('status', 'active')->where('issued_at', '<=', $cutoff))
            ->whereDoesntHave('activities', $recent)
            ->whereDoesntHave('partneredActivities', $recent);
    }

    /**
     * Files an application with its uploaded requirements. Shared by the CSO form and PESO's
     * assisted encoding, which differ only in who submits and the channel recorded.
     *
     * @param  array<string, UploadedFile|null>  $files  keyed by config('document_types')
     * @param  array<string, string|null>  $expiries
     */
    public function submitApplication(string $type, array $files, array $expiries, User $submittedBy, string $channel): ApplicationModel
    {
        return DB::transaction(function () use ($type, $files, $expiries, $submittedBy, $channel) {
            $application = $this->applications()->create([
                'type' => $type,
                'status' => 'submitted',
                'submission_channel' => $channel,
                'submitted_by' => $submittedBy->id,
                'submitted_at' => now(),
            ]);

            foreach (array_keys(config('document_types')) as $documentType) {
                if (! $file = $files[$documentType] ?? null) {
                    continue;
                }

                $this->documents()->create([
                    'application_id' => $application->id,
                    'document_type' => $documentType,
                    'file_path' => $file->store("documents/{$this->id}"),
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'expires_at' => ($expiries[$documentType] ?? null) ?: null,
                ]);
            }

            return $application;
        });
    }

    /** Logs a pending activity and tags its partner organizations (validated StoreActivityRequest data). */
    public function logActivity(array $data, User $loggedBy): Activity
    {
        return DB::transaction(function () use ($data, $loggedBy) {
            $activity = $this->activities()->create(Arr::except($data, 'partners') + [
                'logged_by' => $loggedBy->id,
                'status' => 'pending',
            ]);
            $activity->partnerOrganizations()->sync($data['partners'] ?? []);

            return $activity;
        });
    }

    /** @return array<int, string> every other organization, id => name, for the partner picker */
    public static function partnerOptions(Organization $except): array
    {
        return static::whereKeyNot($except->id)->orderBy('name')->pluck('name', 'id')->all();
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
