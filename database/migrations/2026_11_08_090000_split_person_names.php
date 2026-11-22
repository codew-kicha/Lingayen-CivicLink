<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Names in parts (pre-final defense panel): last, first, middle and, for accounts, a suffix. `name`
// stays as the composed display name, so existing screens keep working. Existing rows are split on a
// best-effort basis; people can correct theirs in Account settings or the organization profile.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('suffix', 10)->nullable()->after('middle_name');
        });
        Schema::table('organization_members', function (Blueprint $table) {
            $table->string('last_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('last_name');
            $table->string('middle_name')->nullable()->after('first_name');
        });

        foreach (['users', 'organization_members'] as $table) {
            DB::table($table)->orderBy('id')->each(function ($row) use ($table) {
                $parts = User::splitName((string) $row->name);
                if ($table === 'organization_members') {
                    unset($parts['suffix']);
                }
                DB::table($table)->where('id', $row->id)->update($parts);
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['last_name', 'first_name', 'middle_name', 'suffix']));
        Schema::table('organization_members', fn (Blueprint $table) => $table->dropColumn(['last_name', 'first_name', 'middle_name']));
    }
};
