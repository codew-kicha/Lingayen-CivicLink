<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('activity_frequency', 5, 4);
            $table->decimal('community_reach', 5, 4);
            $table->decimal('compliance_timeliness', 5, 4);
            $table->decimal('document_currency', 5, 4);
            $table->decimal('total_score', 5, 4);
            $table->timestamp('computed_at');
            $table->timestamps();
            $table->unique(['organization_id', 'period_start', 'period_end'], 'performance_scores_org_period_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_scores');
    }
};
