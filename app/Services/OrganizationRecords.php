<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Cleaning up the directory: deleting a test or mistaken record, and merging a duplicate into the
 * record that should remain. Both are recorded in the audit log by name, since the deleted
 * organization's own row is gone afterwards.
 */
class OrganizationRecords
{
    public function __construct(private PerformanceScoreCalculator $scores) {}

    /** What deleting would remove, for the confirmation dialog and the audit entry. */
    public static function footprint(Organization $organization): array
    {
        return [
            'applications' => $organization->applications()->count(),
            'accreditations' => $organization->accreditations()->count(),
            'activities' => $organization->activities()->count(),
            'documents' => $organization->documents()->count(),
            'members' => $organization->members()->count(),
        ];
    }

    /**
     * Deletes the organization with everything that belongs to it (applications, accreditations,
     * activities, documents, members, scores, tags), its representative's login, and its files.
     */
    public function delete(Organization $organization): void
    {
        $files = $this->privateFiles($organization);
        $logo = $organization->logo_path;
        $user = $organization->user;

        DB::transaction(function () use ($organization, $user) {
            AuditLog::record('organization.deleted', $organization, [
                'name' => $organization->name,
                'representative' => $user?->email,
            ] + self::footprint($organization));

            $organization->delete();   // the database cascades to everything that belongs to it

            if ($user) {
                $user->notifications()->delete();
                $user->delete();
            }
        });

        Storage::disk('local')->delete($files);
        if ($logo) {
            Storage::disk('public')->delete($logo);
        }
    }

    /**
     * Moves everything from a duplicate onto the record that stays, then deletes the duplicate.
     *
     * - Applications, accreditations, activities and documents move across. If both have an active
     *   accreditation, the remaining record keeps its own and the duplicate's becomes expired.
     * - Partner and news tags move across without doubling up or tagging an organization on its own activity.
     * - Officers and members move only if the remaining record has none; blank profile fields are filled in.
     * - The duplicate's representative becomes the remaining record's, if it has none; otherwise
     *   that login is deactivated (one login per organization).
     *
     * @return array<string, int> what moved
     */
    public function merge(Organization $from, Organization $into): array
    {
        $orphanLogo = null;

        $moved = DB::transaction(function () use ($from, $into, &$orphanLogo) {
            $moved = self::footprint($from);

            $intoHasActive = $into->accreditations()->where('status', 'active')->exists();
            foreach ($from->accreditations()->get() as $accreditation) {
                $changes = ['organization_id' => $into->id];
                if ($accreditation->status === 'active') {
                    $changes += $intoHasActive
                        ? ['status' => 'expired', 'active_org_marker' => null]
                        : ['active_org_marker' => $into->id];
                }
                $accreditation->update($changes);
            }

            $from->applications()->update(['organization_id' => $into->id]);
            $from->documents()->update(['organization_id' => $into->id]);
            $from->activities()->update(['organization_id' => $into->id]);

            // Partner tags. Ids are read first: MySQL can't delete from a table it is also selecting from.
            $intoActivities = Activity::where('organization_id', $into->id)->pluck('id');
            $intoTagged = DB::table('activity_organization')->where('organization_id', $into->id)->pluck('activity_id');
            DB::table('activity_organization')->where('organization_id', $from->id)
                ->whereIn('activity_id', $intoActivities->merge($intoTagged))->delete();
            DB::table('activity_organization')->where('organization_id', $from->id)->update(['organization_id' => $into->id]);
            DB::table('activity_organization')->where('organization_id', $into->id)->whereIn('activity_id', $intoActivities)->delete();

            $intoPosts = DB::table('news_post_organization')->where('organization_id', $into->id)->pluck('news_post_id');
            DB::table('news_post_organization')->where('organization_id', $from->id)->whereIn('news_post_id', $intoPosts)->delete();
            DB::table('news_post_organization')->where('organization_id', $from->id)->update(['organization_id' => $into->id]);

            if (! $into->members()->exists()) {
                $from->members()->update(['organization_id' => $into->id]);
            }

            foreach (['advocacy', 'org_chart', 'logo_path'] as $field) {
                if (blank($into->{$field}) && filled($from->{$field})) {
                    $into->{$field} = $from->{$field};
                }
            }
            if ($from->logo_path && $from->logo_path !== $into->logo_path) {
                $orphanLogo = $from->logo_path;
            }

            if ($representative = $from->user) {
                $from->forceFill(['user_id' => null])->save();
                if ($into->user_id === null) {
                    $into->user_id = $representative->id;
                } else {
                    $representative->forceFill([
                        'is_active' => false,
                        'deactivated_at' => now(),
                        'deactivation_reason' => "Organization merged into {$into->name}, which already has a login.",
                    ])->save();
                }
            }

            $into->save();

            AuditLog::record('organization.merged', $into, ['from' => $from->name, 'from_id' => $from->id] + $moved);
            $from->delete();

            return $moved;
        });

        if ($orphanLogo) {
            Storage::disk('public')->delete($orphanLogo);
        }
        $this->scores->recalculate($into->refresh());

        return $moved;
    }

    /** @return list<string> uploaded documents and sign-in sheets on the private disk */
    private function privateFiles(Organization $organization): array
    {
        return $organization->documents()->pluck('file_path')
            ->merge($organization->activities()->whereNotNull('evidence_path')->pluck('evidence_path'))
            ->filter()->values()->all();
    }
}
