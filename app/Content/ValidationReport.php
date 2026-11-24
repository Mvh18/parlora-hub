<?php

namespace App\Content;

/**
 * Everything validation found: errors block an import, warnings do not.
 */
final class ValidationReport
{
    /** @var list<ContentIssue> */
    private array $errors = [];

    /** @var list<ContentIssue> */
    private array $warnings = [];

    /**
     * The normalised rows of the files that passed the schema, set by the
     * validator. It is only complete when the report has no errors.
     */
    public ?ContentTree $tree = null;

    /** The number of content files that were read. */
    public int $files = 0;

    public function error(string $file, string $pointer, string $message): void
    {
        $this->errors[] = new ContentIssue($file, $pointer, $message);
    }

    public function warning(string $file, string $pointer, string $message): void
    {
        $this->warnings[] = new ContentIssue($file, $pointer, $message);
    }

    /** @return list<ContentIssue> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @return list<ContentIssue> */
    public function warnings(): array
    {
        return $this->warnings;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return ! $this->hasErrors();
    }
}
