<?php

namespace App\Console\Commands;

use App\Services\OrganizationImporter;
use Illuminate\Console\Command;

class ImportOrganizations extends Command
{
    protected $signature = 'organizations:import {file : CSV with name, sector, barangay, and optional advocacy columns} {--dry-run : Check the file without saving}';

    protected $description = "Import the office's existing organization list";

    public function handle(OrganizationImporter $importer): int
    {
        if (! is_readable($file = $this->argument('file'))) {
            $this->error("Cannot read {$file}.");

            return self::FAILURE;
        }

        $result = $importer->import($file, $this->option('dry-run'));

        foreach ($result['errors'] as $line => $error) {
            $this->error("Line {$line}: {$error}");
        }

        if ($result['errors']) {
            $this->warn('Nothing was imported. Fix the lines above and run again.');

            return self::FAILURE;
        }

        foreach ($result['skipped'] as $name) {
            $this->line("Skipped (already on file): {$name}");
        }

        $this->info(($this->option('dry-run') ? 'Would import ' : 'Imported ').$result['created'].' organizations.');

        return self::SUCCESS;
    }
}
