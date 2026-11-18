<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The client (Nov 2026): accreditation is free during the period a DILG memorandum opens after each
// new LGU administration (about three months), and lasts three years. Filed outside it, the fee
// applies (PHP 1,000 new, PHP 500 renewal) and accreditation lasts one year, renewed yearly.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_free_periods', function (Blueprint $table) {
            $table->id();
            $table->string('memorandum', 120);   // e.g. "DILG MC No. 2025-060"
            $table->date('starts_on');
            $table->date('ends_on');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['starts_on', 'ends_on']);
        });

        Schema::table('applications', function (Blueprint $table) {
            // Decided when the application is submitted (inside a free period or not); the Desk
            // Office may change it while the requirements are open. Existing applications paid.
            $table->boolean('fee_waived')->default(false)->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('applications', fn (Blueprint $table) => $table->dropColumn('fee_waived'));
        Schema::dropIfExists('fee_free_periods');
    }
};
