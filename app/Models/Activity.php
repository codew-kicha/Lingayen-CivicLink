<?php

namespace App\Models;

use App\Services\ImageForensics;
use App\Services\SheetChecks;
use App\Services\SheetReader;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        'organization_id', 'logged_by', 'verified_by', 'title', 'description', 'venue',
        'activity_date', 'participants_estimate', 'activity_source', 'status', 'verified_at', 'rejection_reason',
        'evidence_path', 'evidence_filename', 'evidence_mime', 'evidence_hash', 'evidence_phash',
    ];

    /** The automated result (SheetChecks): decision support for the reviewer, never a verdict. */
    public const CHECK_RESULTS = [
        'consistent' => 'Verified',
        'review' => 'Needs review',
        'flagged' => 'Flagged',
    ];

    /** Validated fields that are not columns: files, flags and the sheet text read for the checks. */
    private const NOT_COLUMNS = ['partners', 'evidence', 'remove_evidence', 'evidence_text', 'photos', 'remove_photos'];

    /** The stored attributes of a newly attached sign-in sheet, its fingerprint included when it is a picture. */
    public static function evidenceAttributes(UploadedFile $evidence, int $organizationId): array
    {
        $mime = (string) $evidence->getMimeType();

        return [
            'evidence_path' => $evidence->store("activity-evidence/{$organizationId}"),
            'evidence_filename' => $evidence->getClientOriginalName(),
            'evidence_mime' => $mime,
            'evidence_hash' => hash_file('sha256', $evidence->getRealPath()),
            'evidence_phash' => str_starts_with($mime, 'image/') ? ImageForensics::fingerprint($evidence->getRealPath()) : null,
        ];
    }

    /** Adds uploaded photos, and removes the chosen ones (ids of this activity's photos). */
    public function syncPhotos(array $uploads, array $removeIds = []): bool
    {
        $removed = $this->photos()->whereKey($removeIds)->get();
        foreach ($removed as $photo) {
            Storage::disk('local')->delete($photo->path);
            $photo->delete();
        }
        foreach ($uploads as $file) {
            ActivityPhoto::store($this, $file);
        }

        return $removed->isNotEmpty() || $uploads;
    }

    /**
     * A representative's correction while the activity is still pending (validated StoreActivityRequest
     * data). A new sign-in sheet replaces the old one; `remove_evidence` drops it.
     */
    public function revise(array $data): void
    {
        $evidence = $data['evidence'] ?? null;
        $previous = $this->evidence_path;
        $attributes = Arr::except($data, self::NOT_COLUMNS);

        if ($evidence) {
            $attributes += self::evidenceAttributes($evidence, $this->organization_id);
        } elseif (! empty($data['remove_evidence'])) {
            $attributes += ['evidence_path' => null, 'evidence_filename' => null, 'evidence_mime' => null, 'evidence_hash' => null, 'evidence_phash' => null];
        }

        $photosChanged = DB::transaction(function () use ($attributes, $data) {
            $this->update($attributes);
            $this->partnerOrganizations()->sync($data['partners'] ?? []);

            return $this->syncPhotos($data['photos'] ?? [], $data['remove_photos'] ?? []);
        });

        if ($previous && $previous !== $this->evidence_path) {
            Storage::disk('local')->delete($previous);
        }

        // New or removed evidence is checked again; the AI reading starts over, since it reads the photos too.
        if ($evidence || ! empty($data['remove_evidence']) || $photosChanged) {
            $this->forceFill(['check_findings' => null, 'ai_names_count' => null, 'ai_checked_at' => null]);
        }
        $this->checkSheet($data['evidence_text'] ?? null);
    }

    /**
     * The checks that help the office verify (App\Services\SheetChecks), then, when switched on, the
     * AI reading of the sign-in sheet after the page has answered (App\Services\SheetReader).
     */
    public function checkSheet(?string $text = null): void
    {
        SheetChecks::run($this, $text);

        if ($this->evidence_path && SheetReader::enabled() && ! $this->ai_checked_at) {
            $id = $this->id;
            \Illuminate\Support\defer(fn () => SheetReader::read(Activity::find($id)));
        }
    }

    /** Deletes the activity, its sign-in sheet and its photos (partner tags go with it). */
    public function withdraw(): void
    {
        $paths = $this->photos()->pluck('path')->push($this->evidence_path)->filter()->all();
        $this->delete();

        Storage::disk('local')->delete($paths);
    }

    /** Every check finding (what to look at), free and AI, as one list of sentences. */
    public function checkFindings(): array
    {
        return SheetChecks::findings($this);
    }

    /** What the checks confirmed, as sentences. */
    public function checkConfirmations(): array
    {
        return SheetChecks::confirmations($this);
    }

    /** Verified, Needs review or Flagged; null before the checks have run. */
    public function checkLabel(): ?string
    {
        return self::CHECK_RESULTS[$this->check_status] ?? null;
    }

    public function evidenceIsImage(): bool
    {
        return str_starts_with((string) $this->evidence_mime, 'image/');
    }

    public function sourceLabel(): string
    {
        return self::SOURCES[$this->activity_source] ?? 'Not recorded';
    }

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'verified_at' => 'datetime',
            'check_findings' => 'array',
            'checked_at' => 'datetime',
            'ai_checked_at' => 'datetime',
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

    public function photos(): HasMany
    {
        return $this->hasMany(ActivityPhoto::class)->orderBy('id');
    }
}
