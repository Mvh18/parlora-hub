<?php

namespace App\Content;

use Stringable;

/**
 * A problem found in the content tree: the file (relative to the content
 * root, empty when it concerns the tree as a whole), where inside the file
 * (a JSON pointer) and what is wrong.
 */
final readonly class ContentIssue implements Stringable
{
    public function __construct(
        public string $file,
        public string $pointer,
        public string $message,
    ) {}

    /** `<file> <pointer>: <message>`, the line `content:validate` prints. */
    public function __toString(): string
    {
        return sprintf('%s %s: %s', $this->file === '' ? '(content)' : $this->file, $this->pointer === '' ? '/' : $this->pointer, $this->message);
    }
}
