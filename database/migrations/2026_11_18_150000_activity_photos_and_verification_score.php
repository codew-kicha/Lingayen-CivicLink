<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Automated verification of activity evidence (pre-final defense panel, requirement 6). Each
// activity can carry up to three photos and a venue; every image gets a perceptual fingerprint
// (a reused or re-saved picture is found across all activities) and its camera data (date taken,
// editing software, GPS) is kept for the checks. The checks give a 0-100 confidence score and a
// result: consistent (shown as Verified), review (Needs review) or flagged. Advice for the reviewer.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->string('venue', 150)->nullable()->after('description');
            $table->string('evidence_phash', 16)->nullable()->after('evidence_hash');
            $table->unsignedTinyInteger('check_score')->nullable()->after('check_status');
        });

        // consistent / review / flagged (a third result, Needs review, between the two there were).
        Schema::table('activities', function (Blueprint $table) {
            $table->string('check_status', 20)->nullable()->change();
        });

        Schema::create('activity_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('filename');
            $table->string('mime', 100);
            $table->string('hash', 64)->index();        // the exact file (SHA-256)
            $table->string('phash', 16)->nullable();    // what it looks like (difference hash)
            $table->json('metadata')->nullable();       // taken_at, software, camera, lat, lng
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_photos');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['venue', 'evidence_phash', 'check_score']);
        });
    }
};
