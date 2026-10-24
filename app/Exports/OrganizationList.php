<?php

namespace App\Exports;

use App\Models\Organization;
use Illuminate\Support\Collection;

/** The office's CSO list as one sheet: every registered organization, or only the accredited ones. */
class OrganizationList
{
    public static function sheet(bool $accreditedOnly): ReportSheet
    {
        return new ReportSheet($accreditedOnly ? 'Accredited CSOs' : 'Registered CSOs', [
            'Organization', 'Barangay', 'Sector', 'Advocacy', 'Accreditation', 'Verification code',
            'Accredited since', 'Accredited until', 'Representative', 'Email', 'Mobile number',
            'Verified activities', 'Public listing',
        ], self::organizations($accreditedOnly)->map(function (Organization $organization) {
            $accreditation = $organization->accreditations->first();

            return [
                $organization->name,
                $organization->barangay,
                $organization->sector,
                $organization->advocacy,
                $accreditation ? 'Accredited' : 'Not accredited',
                $accreditation?->verification_code,
                $accreditation?->issued_at?->toDateString(),
                $accreditation?->expires_at?->toDateString(),
                $organization->user?->name,
                $organization->user?->email,
                $organization->user?->phone,
                $organization->verified_activities_count,
                $organization->public_visibility ? 'Listed' : 'Hidden',
            ];
        })->all());
    }

    private static function organizations(bool $accreditedOnly): Collection
    {
        return Organization::query()
            ->with(['accreditations' => fn ($q) => $q->where('status', 'active'), 'user:id,name,email,phone'])
            ->withCount(['activities as verified_activities_count' => fn ($q) => $q->where('status', 'verified')])
            ->when($accreditedOnly, fn ($q) => $q->whereHas('accreditations', fn ($q) => $q->where('status', 'active')))
            ->orderBy('name')
            ->get();
    }
}
