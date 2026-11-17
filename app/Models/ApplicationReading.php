<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One Sangguniang Bayan reading as the Desk Office recorded it (pre-final defense panel: the CSO is
 * told what happened at each reading). A passed reading moves the application to that stage; a
 * deferred or returned one leaves it where it was, and the remarks say why.
 */
class ApplicationReading extends Model
{
    /** outcome => [label, what it means for the CSO] */
    public const OUTCOMES = [
        'passed' => ['Passed', 'The reading was completed and the application moves on.'],
        'deferred' => ['Deferred', 'The Sangguniang Bayan will take it up again at a later session.'],
        'returned' => ['Returned for changes', 'The Sangguniang Bayan asked for changes before it continues.'],
    ];

    protected $fillable = ['application_id', 'stage', 'outcome', 'remarks', 'held_on', 'recorded_by'];

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

    public function stageLabel(): string
    {
        return ApplicationModel::SB_STAGES[$this->stage] ?? $this->stage;
    }

    public function outcomeLabel(): string
    {
        return $this->stage === 'endorsed' ? 'Endorsed' : self::OUTCOMES[$this->outcome][0];
    }

    /** What happens next, in the CSO's terms. */
    public function nextStep(): string
    {
        if ($this->stage === 'endorsed') {
            return 'The Desk Office will now issue your accreditation and you will be notified.';
        }
        if ($this->outcome !== 'passed') {
            return self::OUTCOMES[$this->outcome][1].($this->outcome === 'returned' ? ' The Desk Office will tell you how to send the changes.' : '');
        }
        $next = ApplicationModel::SB_STAGES[array_keys(ApplicationModel::SB_STAGES)[array_search($this->stage, array_keys(ApplicationModel::SB_STAGES), true) + 1]];

        return 'Next: the '.($next === 'Endorsed' ? 'endorsement' : strtolower($next)).'.';
    }
}
