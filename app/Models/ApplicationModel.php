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
     * Pre-final defense panel: the application form goes first and the other requirements follow
     * once the Desk Office approves it. In order, with what each status means to the CSO.
     */
    public const FLOW = [
        'submitted' => 'Application form submitted',
        'requirements_pending' => 'Form approved, requirements pending',
        'requirements_submitted' => 'Requirements submitted',
        'under_review' => 'Sangguniang Bayan readings',
        'approved' => 'Accredited',
    ];

    /** Not decided yet: an organization files no second application while one of these is open. */
    public const OPEN = ['submitted', 'requirements_pending', 'requirements_submitted', 'under_review'];

    /** Waiting on the Desk Office (the queue, the dashboard and the sidebar count). */
    public const WITH_OFFICE = ['submitted', 'requirements_submitted', 'under_review'];

    /**
     * The Sangguniang Bayan stages, in order. The office records them one at a time; an application
     * can be approved (accreditation issued) only once it has reached 'endorsed'.
     */
    public const SB_STAGES = [
        'not_endorsed' => 'Not endorsed',
        'first_reading' => 'First reading',
        'second_reading' => 'Second reading',
        'third_reading' => 'Third reading',
        'endorsed' => 'Endorsed',
    ];

    /** The stage before and after the current one (null at either end). */
    public function neighbouringStages(): array
    {
        $keys = array_keys(self::SB_STAGES);
        $i = array_search($this->sb_stage, $keys, true);

        return [$i > 0 ? $keys[$i - 1] : null, $i !== false && $i < count($keys) - 1 ? $keys[$i + 1] : null];
    }

    protected $fillable = [
        'organization_id', 'type', 'status', 'submission_channel', 'sb_stage',
        'submitted_by', 'reviewed_by', 'rejection_reason', 'submitted_at', 'reviewed_at', 'form_data',
        'form_approved_at', 'form_approved_by', 'requirements_submitted_at', 'requirements_note',
    ];

    protected function casts(): array
    {
        return [
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

    /** The Sangguniang Bayan readings the office recorded, oldest first. */
    public function readings(): HasMany
    {
        return $this->hasMany(ApplicationReading::class, 'application_id')->orderBy('held_on')->orderBy('id');
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
