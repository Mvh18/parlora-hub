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
 * in one transaction and a summary per table is printed. Missing audio files
 * are warnings and never fail the import. With `--dry-run` the same summary
 * is printed and the transaction is rolled back.
 */
#[Signature('content:import
    {--path= : Content folder to import instead of HUB_CONTENT_PATH}
    {--dry-run : Validate and plan, print the summary and write nothing}')]
#[Description('Import the course content files into the database')]
class ContentImportCommand extends Command
{
    /** One column per plan action, in the order of `ImportPlan::ACTIONS`. */
    public const SUMMARY_HEADERS = ['Table', 'Created', 'Updated', 'Unchanged', 'Archived', 'Restored'];

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

        $dryRun = (bool) $this->option('dry-run');
        $plan = $importer->import($report->tree, $dryRun);

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

        $files = sprintf('%d %s', $report->files, $report->files === 1 ? 'file' : 'files');

        $this->components->info($dryRun
            ? "Dry run of {$files}: nothing was written."
            : "Content imported from {$files}.");

        return self::SUCCESS;
    }

    private function summary(ImportPlan $plan): void
    {
        $rows = [];

        foreach (ContentTables::order() as $table) {
            $rows[] = [
                $table,
                ...array_map(fn (string $action): int => $plan->count($table, $action), ImportPlan::ACTIONS),
            ];
        }

        $this->table(self::SUMMARY_HEADERS, $rows);
    }
}
