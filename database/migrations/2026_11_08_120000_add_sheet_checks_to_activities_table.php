<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Help for verifying many activities (pre-final defense panel). Every logged activity gets free
// checks (App\Services\SheetChecks); with an API key, an AI reading of its sign-in sheet as well
// (App\Services\SheetReader). The sheet's fingerprint finds the same file attached twice. The text
// read from a sheet is never stored, only what was found.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('evidence_hash', 64)->nullable()->index()->after('evidence_mime');
            $table->enum('check_status', ['consistent', 'flagged', 'failed'])->nullable()->after('evidence_hash');
            $table->json('check_findings')->nullable()->after('check_status');
            $table->unsignedSmallInteger('ai_names_count')->nullable()->after('check_findings');
            $table->timestamp('checked_at')->nullable()->after('ai_names_count');
            $table->timestamp('ai_checked_at')->nullable()->after('checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['evidence_hash']);
            $table->dropColumn(['evidence_hash', 'check_status', 'check_findings', 'ai_names_count', 'checked_at', 'ai_checked_at']);
        });
    }
};
