<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A person's name, or one part of it: letters (ñ and accented letters included), spaces, hyphens,
 * apostrophes and periods, starting with a letter. "Ma. Cristina", "Dela Cruz-Santos" and "O'Neil"
 * pass; numbers and other symbols don't. Used on every name field; the inputs carry the same
 * PATTERN so the browser says so as the name is typed (resources/js/app.js, data-person-name).
 * Organization names are not person names and may contain numbers ("Purok 3 Women's Association").
 */
class PersonName implements ValidationRule
{
    public const REGEX = "/^[\pL\pM][\pL\pM .'’-]*$/u";

    /** The same rule for an input's pattern attribute (browsers read it with the v flag); spaces around
     *  the name are allowed there, as the server trims them. */
    public const PATTERN = "\\s*[\\p{L}\\p{M}][\\p{L}\\p{M} .'’\\-]*";

    public const MESSAGE = 'Use letters only. A name can have spaces, hyphens (-), apostrophes (\') and periods (.), but not numbers or other symbols.';

    public static function valid(?string $value): bool
    {
        return $value === null || trim($value) === '' || preg_match(self::REGEX, trim($value)) === 1;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::valid($value)) {
            $fail(self::MESSAGE);
        }
    }
}
