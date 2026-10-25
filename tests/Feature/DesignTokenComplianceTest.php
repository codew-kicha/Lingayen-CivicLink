<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Keeps the design system honest (docs/DESIGN_SYSTEM.md): colours come only from
 * resources/css/tokens.css, and padding/margin/gap stay on the 8-pt scale. PDF views are exempt,
 * since dompdf renders plain CSS without Tailwind or the tokens.
 */
class DesignTokenComplianceTest extends TestCase
{
    /** @return array<string, string> path => contents */
    private function sources(): array
    {
        return collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->reject(fn ($file) => str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'pdf'.DIRECTORY_SEPARATOR)
                || str_contains($file->getFilename(), '-pdf.'))
            ->push(new \SplFileInfo(resource_path('css/app.css')))
            ->mapWithKeys(fn ($file) => [str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname()) => File::get($file->getPathname())])
            ->all();
    }

    public function test_no_hard_coded_colours_outside_the_tokens_file(): void
    {
        $offenders = [];
        foreach ($this->sources() as $path => $contents) {
            if (preg_match_all('/#[0-9a-fA-F]{6}\b|#[0-9a-fA-F]{3}\b(?![\w-])|\boklch\(|\bhsla?\(|\brgba?\((?!var\()/', $contents, $m)) {
                $offenders[] = "$path: ".implode(', ', array_unique($m[0]));
            }
        }

        $this->assertSame([], $offenders, "Use a token from resources/css/tokens.css instead:\n".implode("\n", $offenders));
    }

    public function test_spacing_stays_on_the_scale(): void
    {
        $allowed = '0|px|hair|1|tight|2|3|4|6|8|12|section|auto';
        $pattern = '/(?<![\w-])(?:[a-z0-9]+:)*-?(?>gap-x|gap-y|space-x|space-y|gap|px|py|pt|pb|pl|pr|ps|pe|p|mx|my|mt|mb|ml|mr|ms|me|m)-(?!(?:'.$allowed.')(?![\w.]))([\w.\[\]]+)/';

        $offenders = [];
        foreach ($this->sources() as $path => $contents) {
            if (preg_match_all($pattern, $contents, $m)) {
                $offenders[] = "$path: ".implode(', ', array_unique($m[0]));
            }
        }

        $this->assertSame([], $offenders, "Off-scale spacing (allowed: $allowed):\n".implode("\n", $offenders));
    }
}
