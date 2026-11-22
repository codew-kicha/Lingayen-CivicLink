<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Pre-final defense panel: the application form is submitted on its own first, and the other
// requirements (official receipt, constitution, DOLE/SEC) are uploaded only after the Desk Office
// approves it. Flow: submitted -> requirements_pending -> requirements_submitted -> under_review
// (the Sangguniang Bayan's readings) -> approved (accredited), or rejected at any open point.
return new class extends Migration
{
    private const BEFORE = ['draft', 'submitted', 'under_review', 'approved', 'rejected'];

    private const AFTER = ['draft', 'submitted', 'requirements_pending', 'requirements_submitted', 'under_review', 'approved', 'rejected'];

    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->enum('status', self::AFTER)->default('draft')->change();
            $table->timestamp('form_approved_at')->nullable()->after('submitted_at');
            $table->foreignId('form_approved_by')->nullable()->after('form_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('requirements_submitted_at')->nullable()->after('form_approved_by');
            // Why the office sent the requirements back, shown to the CSO until they resubmit.
            $table->text('requirements_note')->nullable()->after('requirements_submitted_at');
        });
    }

    public function down(): void
    {
        // Applications in the new states go back to the closest old one.
        DB::table('applications')->whereIn('status', ['requirements_pending', 'requirements_submitted'])->update(['status' => 'submitted']);

        Schema::table('applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('form_approved_by');
            $table->dropColumn(['form_approved_at', 'requirements_submitted_at', 'requirements_note']);
            $table->enum('status', self::BEFORE)->default('draft')->change();
        });
    }
};
