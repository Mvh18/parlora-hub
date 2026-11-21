<?php

namespace App\Content;

use stdClass;

/**
 * A content file that was read and decoded, with what its path says about it.
 */
final readonly class ContentFile
{
    /**
     * @param  string  $path  relative to the content root, with forward slashes
     * @param  string  $kind  one of SchemaValidator::KINDS
     * @param  string|null  $folder  the level folder (`a1`) for files under `levels/`
     * @param  bool  $placement  whether this is `exams/placement.json`
     * @param  stdClass|list<mixed>|scalar|null  $document  decoded with objects, for the schema
     * @param  array<array-key, mixed>|scalar|null  $data  decoded with arrays, for the rows
     */
    public function __construct(
        public string $path,
        public string $kind,
        public ?string $folder,
        public bool $placement,
        public mixed $document,
        public mixed $data,
    ) {}

    /** The file name without folders, e.g. `01-greetings.json`. */
    public function name(): string
    {
        return basename($this->path);
    }
}
