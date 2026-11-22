<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Date-only columns now hold "2026-11-22" on every database (App\Casts\DateOnly). MySQL's DATE
// columns never kept a time, so nothing changes there; on SQLite (the demo copy) the "00:00:00" that
// Laravel's `date` cast wrote is cut off, so comparisons with a plain date include that day again.
return new class extends Migration
{
    private const COLUMNS = [
        'accreditations' => ['issued_at', 'expires_at'],
        'activities' => ['activity_date'],
        'documents' => ['expires_at'],
        'fee_free_periods' => ['starts_on', 'ends_on'],
        'lgu_events' => ['held_on'],
        'application_hearings' => ['held_on'],
        'performance_scores' => ['period_start', 'period_end'],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                DB::table($table)->whereNotNull($column)->where($column, 'like', '____-__-__ %')
                    ->update([$column => DB::raw("substr({$column}, 1, 10)")]);
            }
        }
    }

    public function down(): void
    {
        // Nothing to restore: a date without its "00:00:00" is the same date.
    }
};
