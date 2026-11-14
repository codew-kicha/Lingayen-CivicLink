<?php

namespace App\Models;

use App\Models\Concerns\HasPersonName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationMember extends Model
{
    use HasPersonName;

    /** The one position every member list must have (pre-final defense: the only one required). */
    public const REPRESENTATIVE = 'Representative';

    /** At least this many members before an organization can apply. */
    public const MINIMUM = 5;

    public const POSITION_SUGGESTIONS = ['President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor', 'P.R.O.', 'Member'];

    /** Validation for a submitted member list (`members[i][...]`), used by the profile and the application. */
    public static function rules(): array
    {
        return [
            'members' => ['required', 'array', 'min:'.self::MINIMUM, 'max:300'],
            'members.*.last_name' => ['required', 'string', 'max:100'],
            'members.*.first_name' => ['required', 'string', 'max:100'],
            'members.*.middle_name' => ['nullable', 'string', 'max:100'],
            'members.*.position' => ['nullable', 'string', 'max:100'],
            'members.*.contact_number' => ['nullable', 'string', 'max:40'],
            'members.*.email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public static function messages(): array
    {
        return [
            'members.required' => 'List at least '.self::MINIMUM.' members, including the representative.',
            'members.min' => 'List at least '.self::MINIMUM.' members, including the representative.',
            'members.*.last_name.required' => 'Enter the last name.',
            'members.*.first_name.required' => 'Enter the first name.',
            'members.*.email.email' => 'Enter a valid email or leave it blank.',
        ];
    }

    /** The list must name exactly one representative. Returns the problem, or null. */
    public static function representativeProblem(array $members): ?string
    {
        $count = collect($members)->filter(fn ($m) => strcasecmp(trim((string) ($m['position'] ?? '')), self::REPRESENTATIVE) === 0)->count();

        return match (true) {
            $count === 0 => 'Mark one member as the Representative.',
            $count > 1 => 'Only one member can be the Representative.',
            default => null,
        };
    }

    protected $fillable = ['organization_id', 'name', 'last_name', 'first_name', 'middle_name', 'position', 'contact_number', 'email'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
