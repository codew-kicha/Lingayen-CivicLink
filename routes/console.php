<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Support\GoLiveChecks;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run before going live and after any server change. Exits with an error if a check fails.
// The admin System check page runs the same checks on the web server's own PHP.
Artisan::command('app:go-live-check {--no-probe : Skip requesting private files from APP_URL}', function () {
    $results = (new GoLiveChecks)->run(probeUrls: ! $this->option('no-probe'));

    $this->table(['', 'Check', 'Result'], array_map(fn ($check) => [
        ['pass' => '<fg=green>PASS</>', 'warn' => '<fg=yellow>WARN</>', 'fail' => '<fg=red>FAIL</>'][$check['status']],
        $check['label'],
        $check['detail'],
    ], $results));

    if (GoLiveChecks::failed($results)) {
        $this->error('Not ready to go live. Fix every FAIL above.');

        return 1;
    }

    $this->info('Ready to go live.');

    return 0;
})->purpose('Check that this server is safe to put online');

// Same as the daily check the website runs on its first visit; useful right after changing dates.
Artisan::command('accreditations:expire', function () {
    $count = \App\Models\Accreditation::expireLapsed();
    $this->info("Expired {$count} accreditation(s) past their expiry date.");
})->purpose('Mark accreditations past their expiry date as expired');

// The live site is never seeded (the demo password is public), so the first administrator is made here.
Artisan::command('app:create-admin', function () {
    $name = $this->ask('Full name');
    $email = strtolower(trim((string) $this->ask('Email (their sign-in)')));
    $password = $this->secret('Password (at least 12 characters, mixed case, a number and a symbol)');

    $validator = Validator::make(compact('name', 'email', 'password'), [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
        'password' => ['required', Password::defaults()],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $message) {
            $this->error($message);
        }

        return 1;
    }

    $admin = User::create(['name' => $name, 'email' => $email, 'password' => $password, 'role' => 'admin']);
    $admin->forceFill(['email_verified_at' => now()])->save();
    AuditLog::record('account.admin_created', $admin, ['email' => $email, 'via' => 'console']);

    $this->info("Administrator {$email} created. Sign in at ".route('login'));

    return 0;
})->purpose('Create a PESO administrator account');
