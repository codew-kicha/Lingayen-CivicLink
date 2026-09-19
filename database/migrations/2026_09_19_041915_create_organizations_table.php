<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sector');
            $table->string('barangay');
            $table->string('barangay_psgc_code')->nullable();
            $table->text('advocacy')->nullable();
            $table->text('org_chart')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('public_visibility')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
