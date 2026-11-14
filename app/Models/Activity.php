<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'organization_id', 'logged_by', 'verified_by', 'title', 'description',
        'activity_date', 'participants_estimate', 'activity_source', 'status', 'verified_at', 'rejection_reason',
        'evidence_path', 'evidence_filename', 'evidence_mime', 'evidence_hash',
    ];

    /**
     * A representative's correction while the activity is still pending (validated StoreActivityRequest
     * data). A new sign-in sheet replaces the old one; `remove_evidence` drops it.
     */
    public function revise(array $data): void
    {
        $evidence = $data['evidence'] ?? null;
        $previous = $this->evidence_path;
        $attributes = Arr::except($data, ['partners', 'evidence', 'remove_evidence', 'evidence_text']);

        if ($evidence) {
            $attributes += [
                'evidence_path' => $evidence->store("activity-evidence/{$this->organization_id}"),
                'evidence_filename' => $evidence->getClientOriginalName(),
                'evidence_mime' => $evidence->getMimeType(),
                'evidence_hash' => hash_file('sha256', $evidence->getRealPath()),
            ];
        } elseif (! empty($data['remove_evidence'])) {
            $attributes += ['evidence_path' => null, 'evidence_filename' => null, 'evidence_mime' => null, 'evidence_hash' => null];
        }

        DB::transaction(function () use ($attributes, $data) {
            $this->update($attributes);
            $this->partnerOrganizations()->sync($data['partners'] ?? []);
        });

        if ($previous && $previous !== $this->evidence_path) {
            Storage::disk('local')->delete($previous);
        }

        // A new or removed sheet is checked again; the AI reading starts over for a new file.
        if ($evidence || ! empty($data['remove_evidence'])) {
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
        \App\Services\SheetChecks::run($this, $text);

        if ($this->evidence_path && \App\Services\SheetReader::enabled() && ! $this->ai_checked_at) {
            $id = $this->id;
            \Illuminate\Support\defer(fn () => \App\Services\SheetReader::read(Activity::find($id)));
        }
    }

    /** Deletes the activity and its sign-in sheet (partner tags go with it). */
    public function withdraw(): void
    {
        $this->delete();

        if ($this->evidence_path) {
            Storage::disk('local')->delete($this->evidence_path);
        }
    }

    /** Every check finding, free and AI, as one list of sentences. */
    public function checkFindings(): array
    {
        return \App\Services\SheetChecks::findings($this);
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
}
