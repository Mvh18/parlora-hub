<?php

namespace App\Console\Commands;

use App\Content\ContentImporter;
use App\Content\ContentPath;
use App\Content\ContentTables;
use App\Content\ContentTree;
use App\Content\ContentValidator;
use App\Content\ImportPlan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Builds or updates the course from the content files. Validation runs first
 * and nothing is written when it fails; otherwise the whole tree is written
 * in one transaction and a summary per table is printed.
 */
#[Signature('content:import {--path= : Content folder to import instead of HUB_CONTENT_PATH}')]
#[Description('Import the course content files into the database')]
class ContentImportCommand extends Command
{
    public function handle(ContentValidator $validator, ContentImporter $importer): int
    {
        $path = $this->option('path');
        $root = ContentPath::resolve(is_string($path) ? $path : null);

        $report = $validator->validate($root);

        if ($report->hasErrors() || ! $report->tree instanceof ContentTree) {
            foreach ($report->errors() as $issue) {
                $this->line((string) $issue);
            }

            $errors = count($report->errors());
            $this->components->error(sprintf('%d %s: nothing was imported.', $errors, $errors === 1 ? 'error' : 'errors'));

            return self::FAILURE;
        }

        $plan = $importer->import($report->tree);

        if ($plan->hasErrors()) {
            foreach ($plan->errors() as $issue) {
                $this->line((string) $issue);
            }

            $errors = count($plan->errors());
            $this->components->error(sprintf('%d %s: nothing was imported.', $errors, $errors === 1 ? 'error' : 'errors'));

            return self::FAILURE;
        }

        $this->summary($plan);

        foreach ($report->warnings() as $issue) {
            $this->line('warning '.$issue);
        }

        $this->components->info(sprintf('Content imported from %d %s.', $report->files, $report->files === 1 ? 'file' : 'files'));

        return self::SUCCESS;
    }

    private function summary(ImportPlan $plan): void
    {
        $rows = [];

        foreach (ContentTables::order() as $table) {
            $rows[] = [
                $table,
                $plan->count($table, ImportPlan::CREATE),
                $plan->count($table, ImportPlan::UPDATE),
                $plan->count($table, ImportPlan::UNCHANGED),
            ];
        }

        $this->table(['Table', 'Created', 'Updated', 'Unchanged'], $rows);
    }
}
