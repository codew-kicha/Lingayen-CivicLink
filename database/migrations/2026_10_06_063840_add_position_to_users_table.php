<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The person's role or title ("President", "PESO Officer"), kept apart from their name.
    // Optional, and not backfilled: existing accounts fill it in from Account settings.
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('position', 120)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('position');
        });
    }
};
