<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ApplicationModel extends Model
{
    use HasFactory;

    protected $table = 'applications';

    /**
     * The client's process (Nov 2026), in order: the organization files, the Desk Office validates
     * the form and then the requirements (the panel's order: nothing paid before the form is
     * approved), the Sangguniang Bayan committee hears it, and the office approves it.
     */
    public const PHASES = [
        'application' => 'Application',
        'validation' => 'Validation',
        'hearing' => 'Committee hearing',
        'approval' => 'Approval',
    ];

    /** Not decided yet: an organization files no second application while one of these is open. */
    public const OPEN = ['submitted', 'requirements_pending', 'requirements_submitted', 'under_review'];

    /** Waiting on the Desk Office (the queue, the dashboard and the sidebar count). */
    public const WITH_OFFICE = ['submitted', 'requirements_submitted', 'under_review'];

    /** Which of the four phases the application is in (a rejected one stays where it stopped: null). */
    public function phase(): ?string
    {
        return match ($this->status) {
            'draft' => 'application',
            'submitted', 'requirements_pending', 'requirements_submitted' => 'validation',
            'under_review' => 'hearing',
            'approved' => 'approval',
            default => null,
        };
    }

    /** Where it stands inside its phase, in a few words. */
    public function phaseNote(): string
    {
        return match ($this->status) {
            'draft' => 'Being filled in by the organization',
            'submitted' => 'Application form being validated',
            'requirements_pending' => $this->requirements_note ? 'Requirements returned for correction' : 'Form approved; requirements to upload',
            'requirements_submitted' => 'Requirements being validated',
            'under_review' => match ($this->hearing_outcome) {
                'favorable' => 'Favorable hearing; ready for approval',
                'deferred' => 'Hearing deferred; to be heard again',
                'returned' => 'Returned by the committee; compliance needed',
                default => 'Waiting for the committee hearing',
            },
            'approved' => 'Approved; accreditation issued',
            'rejected' => 'Not approved',
            default => '',
        };
    }

    /** Ready for the office's approval: heard, and the committee was favorable. */
    public function readyToApprove(): bool
    {
        return $this->status === 'under_review' && $this->hearing_outcome === 'favorable';
    }

    /** While the Desk Office may still waive the fee or require it (before the committee hearing). */
    public const FEE_OPEN = ['submitted', 'requirements_pending', 'requirements_submitted'];

    /** The accreditation fee in pesos: nothing when waived, else PHP 1,000 new or PHP 500 renewal. */
    public function fee(): int
    {
        return $this->fee_waived ? 0 : (int) config("office.fees.{$this->type}", 0);
    }

    /** "PHP 500 renewal fee". */
    public function feeLabel(): string
    {
        return 'PHP '.number_format((int) config("office.fees.{$this->type}", 0)).' '.($this->type === 'renewal' ? 'renewal' : 'accreditation').' fee';
    }

    /** The free period it was submitted in, if any. */
    public function freePeriod(): ?FeeFreePeriod
    {
        return $this->submitted_at ? FeeFreePeriod::covering($this->submitted_at) : null;
    }

    /** Why no fee is due ("filed within the free period under DILG MC No. 2025-060"); null when it is. */
    public function feeWaiverReason(): ?string
    {
        if (! $this->fee_waived) {
            return null;
        }
        $period = $this->freePeriod();

        return $period ? "filed within the free period under {$period->memorandum}" : 'waived by the Desk Office';
    }

    /** What is due, or why nothing is: for the review page and the organization's pages. */
    public function feeNote(): string
    {
        return $this->fee_waived
            ? 'No fee: '.$this->feeWaiverReason()
            : $this->feeLabel().', paid at the Municipal Treasury';
    }

    /** How long the accreditation runs by default: three years if free, one year if paid. */
    public function validityYears(): int
    {
        return (int) config('office.validity_years.'.($this->fee_waived ? 'free' : 'paid'));
    }

    protected $fillable = [
        'organization_id', 'type', 'fee_waived', 'status', 'submission_channel', 'hearing_outcome',
        'submitted_by', 'reviewed_by', 'rejection_reason', 'submitted_at', 'reviewed_at', 'form_data',
        'form_approved_at', 'form_approved_by', 'requirements_submitted_at', 'requirements_note',
    ];

    protected function casts(): array
    {
        return [
            'fee_waived' => 'boolean',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'form_approved_at' => 'datetime',
            'requirements_submitted_at' => 'datetime',
            'form_data' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'application_id');
    }

    public function accreditation(): HasOne
    {
        return $this->hasOne(Accreditation::class, 'application_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** The committee hearings the office recorded, oldest first. */
    public function hearings(): HasMany
    {
        return $this->hasMany(ApplicationHearing::class, 'application_id')->orderBy('held_on')->orderBy('id');
    }

    public function formApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'form_approved_by');
    }

    /** True once the Desk Office has approved the application form (requirements may be uploaded). */
    public function formApproved(): bool
    {
        return in_array($this->status, ['requirements_pending', 'requirements_submitted', 'under_review', 'approved'], true);
    }
}
