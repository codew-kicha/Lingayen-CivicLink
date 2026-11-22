<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// A CSO's own log is self-initiated; LGU-organized credits come only from LGU events the office
// records (pre-final defense panel). Activities still waiting for verification that a CSO marked
// "LGU-organized" under the old form would otherwise be verified that way with nobody checking, so
// they become self-initiated; one that really was an LGU event is linked to the event when reviewed.
// Activities already verified or rejected keep what the office decided.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('activities')->where('status', 'pending')->whereNull('lgu_event_id')
            ->where(fn ($q) => $q->where('activity_source', 'lgu_organized')->orWhereNull('activity_source'))
            ->update(['activity_source' => 'independent']);
    }

    public function down(): void
    {
        // Which ones the CSOs had marked is not kept.
    }
};
