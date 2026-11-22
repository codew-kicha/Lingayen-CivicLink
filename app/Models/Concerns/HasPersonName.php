<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * A person's name kept in parts (last, first, middle and, for users, a suffix), as the panel asked,
 * plus the composed `name` that every screen, email and PDF already shows. Saving with any part
 * set rebuilds `name`; records created with only `name` (older code paths) keep it as given.
 */
trait HasPersonName
{
    public const SUFFIXES = ['Jr.', 'Sr.', 'II', 'III', 'IV'];

    protected static function bootHasPersonName(): void
    {
        static::saving(function ($model) {
            if (filled($model->first_name) || filled($model->last_name)) {
                $model->name = static::composeName($model->first_name, $model->middle_name, $model->last_name, $model->suffix ?? null);
            }
        });
    }

    /** The name in parts; records made before parts existed get a best-effort split of `name`. */
    public function nameParts(): array
    {
        return $this->first_name || $this->last_name
            ? ['last_name' => $this->last_name, 'first_name' => $this->first_name, 'middle_name' => $this->middle_name, 'suffix' => $this->suffix ?? null]
            : static::splitName((string) $this->name);
    }

    /** "Juan D. Dela Cruz Jr.": first name, middle initial, last name, suffix. */
    public static function composeName(?string $first, ?string $middle, ?string $last, ?string $suffix = null): string
    {
        $middleInitial = filled($middle) ? Str::upper(Str::substr(trim($middle), 0, 1)).'.' : null;

        return collect([$first, $middleInitial, $last, $suffix])->map(fn ($part) => trim((string) $part))->filter()->join(' ');
    }

    /**
     * Best-effort split of a full name for records made before the parts existed: a trailing suffix
     * is kept apart, the last word is the last name, everything before it the first name.
     */
    public static function splitName(string $name): array
    {
        $words = preg_split('/\s+/', trim(str_replace(',', ' ', $name)), -1, PREG_SPLIT_NO_EMPTY);
        $suffix = null;
        if (count($words) > 2 && in_array(end($words), self::SUFFIXES, true)) {
            $suffix = array_pop($words);
        }
        $last = count($words) > 1 ? array_pop($words) : null;

        return ['first_name' => implode(' ', $words) ?: null, 'middle_name' => null, 'last_name' => $last, 'suffix' => $suffix];
    }
}
