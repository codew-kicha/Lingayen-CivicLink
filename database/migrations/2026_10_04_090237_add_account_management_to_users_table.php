<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->timestamp('deactivated_at')->nullable()->after('is_active');
            $table->string('deactivation_reason')->nullable()->after('deactivated_at');
            $table->timestamp('last_login_at')->nullable()->after('deactivation_reason');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'is_active', 'deactivated_at', 'deactivation_reason',
                'last_login_at', 'last_login_ip',
            ]);
        });
    }
};
