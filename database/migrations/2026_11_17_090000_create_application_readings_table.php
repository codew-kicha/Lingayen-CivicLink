<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Pre-final defense panel: after each Sangguniang Bayan reading the CSO is told what happened. One
// row per reading the office records: which reading, when the SB held it, the outcome, and the
// remarks (the feedback the CSO receives). The application's sb_stage still says where it stands.
return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('application_readings');
    }
};
