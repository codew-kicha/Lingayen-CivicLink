<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Notices the Desk Office sends to organizations (pre-final defense panel). Each recipient gets it as
// an in-app notification (the notifications table); this keeps the office's own record of what was
// sent, to whom, and how many accounts it reached.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('office_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 150);
            $table->text('body');
            $table->enum('audience', ['organization', 'sector', 'barangay', 'accredited', 'all']);
            $table->string('audience_value')->nullable();
            $table->unsignedInteger('recipients')->default(0);
            $table->boolean('emailed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('office_notices');
    }
};
