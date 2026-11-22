<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// LGU-organized activities come from the office (pre-final defense panel): the Desk Office creates
// and announces an LGU event, then records which organizations attended. Each attending organization
// is credited with an ordinary verified activity linked to the event (activities.lgu_event_id), so the
// score, analytics and public pages count it unchanged. A CSO's own log is always self-initiated.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lgu_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('venue', 150)->nullable();
            $table->date('held_on');
            $table->foreignId('notice_id')->nullable()->constrained('office_notices')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('attendance_recorded_at')->nullable();
            $table->timestamps();

            $table->index('held_on');
        });

        Schema::table('activities', function (Blueprint $table) {
            // Set on an attendance credit; deleting the event removes the credits with it.
            $table->foreignId('lgu_event_id')->nullable()->after('organization_id')->constrained('lgu_events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lgu_event_id');
        });
        Schema::dropIfExists('lgu_events');
    }
};
