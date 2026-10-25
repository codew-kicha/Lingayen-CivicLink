<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// New accounts confirm their email by typing a 6-digit code sent to it (App\Models\User::sendEmailVerificationNotification).
// Only a hash of the code is kept, with its expiry and the number of wrong tries.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_code_hash')->nullable()->after('email_verified_at');
            $table->timestamp('email_code_expires_at')->nullable()->after('email_code_hash');
            $table->unsignedTinyInteger('email_code_attempts')->default(0)->after('email_code_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_code_hash', 'email_code_expires_at', 'email_code_attempts']);
        });
    }
};
