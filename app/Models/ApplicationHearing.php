<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One sitting of the Sangguniang Bayan committee on an application, as the Desk Office recorded it
 * after the hearing (client's process, Nov 2026: Application, Validation, Committee hearing,
 * Approval). The latest outcome is also kept on the application (hearing_outcome).
 */
class ApplicationHearing extends Model
{
    /** outcome => [label, what it means for the CSO, what happens next] */
    public const OUTCOMES = [
        'favorable' => ['Favorable', 'The committee recommends the application for approval.', 'Next: the Desk Office issues your accreditation, and you will be notified.'],
        'deferred' => ['Deferred', 'The committee will hear the application again at a later sitting.', 'Next: the application is heard again; you will be told the outcome.'],
        'returned' => ['Returned', 'The committee asks the organization to comply with something first.', 'Next: do what the remarks ask. The Desk Office will tell you how to send it.'],
    ];

    protected $fillable = ['application_id', 'held_on', 'committee', 'outcome', 'remarks', 'recorded_by'];

    protected function casts(): array
    {
        return ['held_on' => 'date'];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(ApplicationModel::class, 'application_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function outcomeLabel(): string
    {
        return self::OUTCOMES[$this->outcome][0];
    }

    public function meaning(): string
    {
        return self::OUTCOMES[$this->outcome][1];
    }

    public function nextStep(): string
    {
        return self::OUTCOMES[$this->outcome][2];
    }
}
