<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A calendar date with no time of day (an activity's date, an expiry date), stored as "2026-11-22"
 * on every database. Laravel's own `date` cast stores "2026-11-22 00:00:00": MySQL's DATE column
 * drops the time, but SQLite (tests, the demo copy) keeps the text, so a query such as "on or before
 * 2026-11-22" compared text and left out that day's records. Storing the date alone makes every
 * comparison behave the same on both. Reads back as a Carbon at the start of the day, like `date`.
 */
class DateOnly implements CastsAttributes
{
    /** A fresh Carbon on every read, as with `date`, so changing one never changes the model. */
    public bool $withoutObjectCaching = true;

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->toDateString();
    }
}
