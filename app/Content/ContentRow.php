<?php

namespace App\Content;

/**
 * One row of the content tree in normalised form: what the importer needs to
 * write it and what the validator needs to point at it.
 */
final readonly class ContentRow
{
    /**
     * @param  string|null  $parentKey  the key of the parent row; null for roots and the placement exam
     * @param  int|null  $position  1-based position among its siblings; null for unpositioned tables
     * @param  array<string, mixed>  $attributes  the row's own columns, optional ones as null
     * @param  string|null  $passageKey  the key of the passage the row refers to
     * @param  string  $file  the file the row came from, relative to the content root
     * @param  string  $pointer  the JSON pointer of the row inside that file ('' for the whole file)
     */
    public function __construct(
        public string $table,
        public string $key,
        public ?string $parentKey,
        public ?int $position,
        public array $attributes,
        public ?string $passageKey,
        public string $file,
        public string $pointer,
    ) {}
}
