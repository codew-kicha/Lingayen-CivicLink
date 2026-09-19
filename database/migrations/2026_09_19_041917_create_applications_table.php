<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['new', 'renewal']);
            $table->enum('status', ['draft', 'submitted', 'under_review', 'approved', 'rejected'])->default('draft');
            $table->enum('submission_channel', ['online', 'assisted'])->default('online');
            $table->enum('sb_stage', ['not_endorsed', 'first_reading', 'second_reading', 'third_reading', 'endorsed'])->default('not_endorsed');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
