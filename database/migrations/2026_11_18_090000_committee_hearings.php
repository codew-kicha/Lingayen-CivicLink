<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The client's process (Nov 2026): Application, Validation, Committee hearing, Approval. The
// Sangguniang Bayan holds one committee hearing, not first to third readings. The reading ladder
// (applications.sb_stage, application_readings) becomes one recorded hearing per sitting and the
// latest outcome on the application.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_hearings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->date('held_on');
            $table->string('committee', 120)->nullable();
            $table->enum('outcome', ['favorable', 'deferred', 'returned']);
            $table->text('remarks')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['application_id', 'held_on']);
        });

        Schema::table('applications', function (Blueprint $table) {
            // The latest hearing's outcome; null until the committee has heard the application.
            $table->enum('hearing_outcome', ['favorable', 'deferred', 'returned'])->nullable()->after('status');
        });

        // An endorsed application was, in effect, favorably heard.
        DB::table('applications')->where('sb_stage', 'endorsed')->update(['hearing_outcome' => 'favorable']);

        // Old reading records: the endorsement counts as a favorable hearing; deferred and returned
        // readings stay what they were; passed readings before it were never hearings.
        if (Schema::hasTable('application_readings')) {
            $readings = DB::table('application_readings')->orderBy('id')->get();

            $readings->each(function ($reading) {
                $outcome = match (true) {
                    $reading->outcome === 'deferred' => 'deferred',
                    $reading->outcome === 'returned' => 'returned',
                    $reading->stage === 'endorsed' => 'favorable',
                    default => null,
                };
                if ($outcome) {
                    DB::table('application_hearings')->insert([
                        'application_id' => $reading->application_id,
                        'held_on' => $reading->held_on,
                        'outcome' => $outcome,
                        'remarks' => $reading->remarks,
                        'recorded_by' => $reading->recorded_by,
                        'created_at' => $reading->created_at,
                        'updated_at' => $reading->updated_at,
                    ]);
                }
            });

            // Still waiting on the Sangguniang Bayan and last deferred or returned: that is where it stands.
            $readings->groupBy('application_id')->each(function ($history, $applicationId) {
                $last = $history->last();
                if (in_array($last->outcome, ['deferred', 'returned'], true)) {
                    DB::table('applications')->where('id', $applicationId)->where('status', 'under_review')
                        ->whereNull('hearing_outcome')->update(['hearing_outcome' => $last->outcome]);
                }
            });
            Schema::drop('application_readings');
        }

        Schema::table('applications', fn (Blueprint $table) => $table->dropColumn('sb_stage'));
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('sb_stage', ['not_endorsed', 'first_reading', 'second_reading', 'third_reading', 'endorsed'])
                ->default('not_endorsed')->after('status');
        });
        DB::table('applications')->where('hearing_outcome', 'favorable')->update(['sb_stage' => 'endorsed']);

        Schema::create('application_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->enum('stage', ['first_reading', 'second_reading', 'third_reading', 'endorsed']);
            $table->enum('outcome', ['passed', 'deferred', 'returned']);
            $table->text('remarks')->nullable();
            $table->date('held_on');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['application_id', 'created_at']);
        });

        Schema::table('applications', fn (Blueprint $table) => $table->dropColumn('hearing_outcome'));
        Schema::drop('application_hearings');
    }
};
