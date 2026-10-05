<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Who initiated the activity. Nullable so activities logged before this column existed read as
    // "Not recorded" instead of being given a guessed value; the forms require it from now on.
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->enum('activity_source', ['independent', 'lgu_organized'])->nullable()->after('participants_estimate');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('activity_source');
        });
    }
};
