<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accreditations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('verification_code')->unique();
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            // Mirrors organization_id only while status = 'active', otherwise null.
            // A unique index on a nullable column lets MySQL enforce "at most one
            // active accreditation per organization" without a partial index.
            $table->unsignedBigInteger('active_org_marker')->nullable()->unique();
            $table->date('issued_at');
            $table->date('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accreditations');
    }
};
