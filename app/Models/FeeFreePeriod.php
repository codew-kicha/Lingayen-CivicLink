<?php

namespace App\Models;

use App\Casts\DateOnly;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A period in which accreditation is free: the window a DILG memorandum opens after each new LGU
 * administration (client, Nov 2026). An application submitted inside one needs no official receipt
 * and is accredited for three years; outside every period the fee applies and it lasts one year.
 */
class FeeFreePeriod extends Model
{
    protected $fillable = ['memorandum', 'starts_on', 'ends_on', 'created_by'];

    protected function casts(): array
    {
        return [
            'starts_on' => DateOnly::class,
            'ends_on' => DateOnly::class,
        ];
    }

    /** The period that includes $date (the latest to start, if two overlap). */
    public static function covering(CarbonInterface $date): ?self
    {
        return static::whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)
            ->orderByDesc('starts_on')->first();
    }

    /** The period open today, if any. */
    public static function open(): ?self
    {
        return static::covering(now());
    }

    /** The next period still to start, if one has been announced. */
    public static function upcoming(): ?self
    {
        return static::whereDate('starts_on', '>', now())->orderBy('starts_on')->first();
    }

    /** open, upcoming or ended. */
    public function status(): string
    {
        return match (true) {
            $this->starts_on->isAfter(today()) => 'upcoming',
            $this->ends_on->isBefore(today()) => 'ended',
            default => 'open',
        };
    }

    /** "1 July 2025 to 30 September 2025". */
    public function dates(): string
    {
        return $this->starts_on->format('j F Y').' to '.$this->ends_on->format('j F Y');
    }

    /** Applications submitted inside this period. */
    public function applicationsCount(): int
    {
        return ApplicationModel::whereNotNull('submitted_at')
            ->whereDate('submitted_at', '>=', $this->starts_on)
            ->whereDate('submitted_at', '<=', $this->ends_on)
            ->count();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
