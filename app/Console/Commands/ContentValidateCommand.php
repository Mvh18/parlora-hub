<?php

namespace App\Console\Commands;

use App\Content\ContentPath;
use App\Content\ContentValidator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Checks every content file without opening a database connection, so it can
 * run in any CI job. Prints one `<file> <pointer>: <message>` line per
 * problem and exits 1 when there is any error; warnings do not fail it.
 */
#[Signature('content:validate {--path= : Content folder to validate instead of HUB_CONTENT_PATH}')]
#[Description('Validate the course content files against their schema and the content rules')]
class ContentValidateCommand extends Command
{
    public function handle(ContentValidator $validator): int
    {
        $path = $this->option('path');
        $root = ContentPath::resolve(is_string($path) ? $path : null);

        $report = $validator->validate($root);

        foreach ($report->errors() as $issue) {
            $this->line((string) $issue);
        }

        foreach ($report->warnings() as $issue) {
            $this->line('warning '.$issue);
        }

        $errors = count($report->errors());
        $warnings = count($report->warnings());

        if ($errors > 0) {
            $this->components->error(sprintf('%d %s, %d %s in %d %s.', $errors, $errors === 1 ? 'error' : 'errors', $warnings, $warnings === 1 ? 'warning' : 'warnings', $report->files, $report->files === 1 ? 'file' : 'files'));

            return self::FAILURE;
        }

        $this->components->info(sprintf('Content is valid: %d %s, %d %s.', $report->files, $report->files === 1 ? 'file' : 'files', $warnings, $warnings === 1 ? 'warning' : 'warnings'));

        return self::SUCCESS;
    }
}
