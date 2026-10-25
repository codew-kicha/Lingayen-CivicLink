<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The sign-in sheet (photo or PDF) PESO checks before verifying an activity. Optional, so activities
// logged before this existed stay valid. Stored on the private disk, like accreditation documents.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('evidence_path')->nullable()->after('participants_estimate');
            $table->string('evidence_filename')->nullable()->after('evidence_path');
            $table->string('evidence_mime')->nullable()->after('evidence_filename');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['evidence_path', 'evidence_filename', 'evidence_mime']);
        });
    }
};
