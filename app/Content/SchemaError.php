<?php

namespace App\Content;

/**
 * One schema violation: where it is in the file (a JSON pointer, empty for
 * the whole document) and what is wrong.
 */
final readonly class SchemaError
{
    public function __construct(
        public string $pointer,
        public string $message,
    ) {}
}
