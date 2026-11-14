<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The accreditation form is filled in on the website (pre-final defense panel): its answers are kept
// on the draft application, and the documents the system produces from them are marked 'generated'.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->json('form_data')->nullable()->after('rejection_reason');
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('ocr_status', ['not_checked', 'pending', 'matched', 'mismatch', 'unreadable', 'generated'])
                ->default('not_checked')->change();
        });
    }

    public function down(): void
    {
        Schema::table('applications', fn (Blueprint $table) => $table->dropColumn('form_data'));
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('ocr_status', ['not_checked', 'pending', 'matched', 'mismatch', 'unreadable'])
                ->default('not_checked')->change();
        });
    }
};
