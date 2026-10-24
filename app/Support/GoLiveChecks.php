<?php

namespace App\Support;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Checks to pass before the site goes live, and to re-run after any server change. Shared by
 * `php artisan app:go-live-check` and the admin System check page, which matters on shared
 * hosts without SSH: the page runs on the web server's own PHP, which can differ from the CLI's.
 *
 * Each check: ['label' => …, 'status' => 'pass' | 'warn' | 'fail', 'detail' => …].
 */
class GoLiveChecks
{
    /**
     * First PHP release on each supported branch with the PHP2Shell fixes (CVE-2026-109618 and
     * CVE-2026-109619, released 21 October 2026). Older branches get no fix at all.
     */
    public const PATCHED_PHP = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];

    /** Files that must never be downloadable; each with text that would prove it was. */
    public const PRIVATE_PATHS = [
        '.env' => 'APP_KEY=',
        'storage/logs/laravel.log' => '.ERROR',
        'composer.json' => '"require"',
        'vendor/composer/installed.json' => '"packages"',
        '.git/config' => '[core]',
    ];

    /** @return list<array{label: string, status: string, detail: string}> */
    public function run(bool $probeUrls = true): array
    {
        return array_values(array_filter([
            self::php(PHP_VERSION),
            $this->environment(),
            $this->debug(),
            $this->appKey(),
            $this->https(),
            $this->demoAccounts(),
            $this->writable(),
            $this->assets(),
            $this->mail(),
            $probeUrls ? $this->privateFiles() : null,
        ]));
    }

    public static function failed(array $results): bool
    {
        return collect($results)->contains('status', 'fail');
    }

    public static function php(string $version): array
    {
        $branch = implode('.', array_slice(explode('.', $version), 0, 2));
        $patched = self::PATCHED_PHP[$branch] ?? null;
        $label = 'PHP version (PHP2Shell)';

        if ($patched === null) {
            return ['label' => $label, 'status' => 'fail', 'detail' => "PHP {$version} is not a supported branch and gets no PHP2Shell fix. Use PHP 8.3 or newer."];
        }

        return version_compare($version, $patched, '>=')
            ? ['label' => $label, 'status' => 'pass', 'detail' => "PHP {$version} includes the October 2026 PHP2Shell fixes."]
            : ['label' => $label, 'status' => 'fail', 'detail' => "PHP {$version} is open to PHP2Shell (remote code execution). Update to {$patched} or newer before going live."];
    }

    private function environment(): array
    {
        return app()->isProduction()
            ? ['label' => 'Environment', 'status' => 'pass', 'detail' => 'APP_ENV is production.']
            : ['label' => 'Environment', 'status' => 'fail', 'detail' => 'APP_ENV is "'.app()->environment().'". Set APP_ENV=production on the live server.'];
    }

    private function debug(): array
    {
        return config('app.debug')
            ? ['label' => 'Debug mode', 'status' => 'fail', 'detail' => 'APP_DEBUG is on: error pages would show code, settings and passwords to visitors. Set APP_DEBUG=false.']
            : ['label' => 'Debug mode', 'status' => 'pass', 'detail' => 'APP_DEBUG is off.'];
    }

    private function appKey(): array
    {
        return filled(config('app.key'))
            ? ['label' => 'App key', 'status' => 'pass', 'detail' => 'APP_KEY is set. Use a new one on the live server (php artisan key:generate), never the development key.']
            : ['label' => 'App key', 'status' => 'fail', 'detail' => 'APP_KEY is empty. Run php artisan key:generate.'];
    }

    private function https(): array
    {
        $https = str_starts_with((string) config('app.url'), 'https://');
        $secureCookies = (bool) config('session.secure');

        return match (true) {
            $https && $secureCookies => ['label' => 'HTTPS', 'status' => 'pass', 'detail' => 'APP_URL is https and session cookies are secure-only.'],
            $https => ['label' => 'HTTPS', 'status' => 'warn', 'detail' => 'APP_URL is https. Also set SESSION_SECURE_COOKIE=true so sign-in cookies never travel unencrypted.'],
            default => ['label' => 'HTTPS', 'status' => 'fail', 'detail' => 'APP_URL is not https. Sign-ins and member data would cross the internet unencrypted.'],
        };
    }

    // The seeded demo accounts' password is public (it is in the repository).
    private function demoAccounts(): array
    {
        $exposed = User::query()
            ->where(fn ($q) => $q->where('email', 'like', '%@example.ph')->orWhere('email', 'admin@lingayen.gov.ph'))
            ->get(['email', 'password'])
            ->filter(fn (User $user) => Hash::check(DatabaseSeeder::DEMO_PASSWORD, $user->password))
            ->pluck('email');

        return $exposed->isEmpty()
            ? ['label' => 'Demo accounts', 'status' => 'pass', 'detail' => 'No account uses the published demo password.']
            : ['label' => 'Demo accounts', 'status' => 'fail', 'detail' => 'These accounts still use the demo password, which is public: '.$exposed->take(5)->join(', ').($exposed->count() > 5 ? ' and '.($exposed->count() - 5).' more' : '').'. Do not seed the live database.'];
    }

    private function writable(): array
    {
        $blocked = collect([storage_path('framework'), storage_path('logs'), storage_path('app'), base_path('bootstrap/cache')])
            ->reject(fn ($path) => is_dir($path) && is_writable($path))
            ->map(fn ($path) => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));

        return $blocked->isEmpty()
            ? ['label' => 'Writable folders', 'status' => 'pass', 'detail' => 'storage and bootstrap/cache are writable.']
            : ['label' => 'Writable folders', 'status' => 'fail', 'detail' => 'Not writable: '.$blocked->join(', ').'. Uploads, sessions and logs would fail.'];
    }

    private function assets(): array
    {
        $missing = collect([
            'public/build/manifest.json (run npm run build)' => public_path('build/manifest.json'),
            'public/storage link (run php artisan storage:link)' => public_path('storage'),
        ])->reject(fn ($path) => file_exists($path))->keys();

        return $missing->isEmpty()
            ? ['label' => 'Built assets and storage link', 'status' => 'pass', 'detail' => 'Styles, scripts and the public storage link are in place.']
            : ['label' => 'Built assets and storage link', 'status' => 'fail', 'detail' => 'Missing: '.$missing->join('; ').'.'];
    }

    private function mail(): array
    {
        return in_array(config('mail.default'), ['log', 'array'], true)
            ? ['label' => 'Email', 'status' => 'warn', 'detail' => 'MAIL_MAILER is "'.config('mail.default').'": invitations and password links would not reach anyone.']
            : ['label' => 'Email', 'status' => 'pass', 'detail' => 'Mail goes out through '.config('mail.default').'. Send yourself an invitation to confirm it arrives.'];
    }

    // Asks the live site for files that must stay private. Some hosts block a site from calling itself,
    // so an unanswered request is a warning, not a pass.
    private function privateFiles(): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $exposed = [];
        $unchecked = 0;

        foreach (self::PRIVATE_PATHS as $path => $proof) {
            try {
                $response = Http::timeout(5)->withoutRedirecting()->get("{$base}/{$path}");
                if ($response->successful() && str_contains($response->body(), $proof)) {
                    $exposed[] = $path;
                }
            } catch (Throwable) {
                $unchecked++;
            }
        }

        return match (true) {
            $exposed !== [] => ['label' => 'Private files', 'status' => 'fail', 'detail' => 'Downloadable by anyone: '.implode(', ', $exposed).'. Point the web root at public/ or add the deny-all .htaccess (docs/DEPLOYMENT.md).'],
            $unchecked > 0 => ['label' => 'Private files', 'status' => 'warn', 'detail' => "Could not reach {$base} to check. Open {$base}/.env in a browser: it must show an error, not settings."],
            default => ['label' => 'Private files', 'status' => 'pass', 'detail' => '.env, logs, vendor, composer and .git files are not downloadable.'],
        };
    }
}
