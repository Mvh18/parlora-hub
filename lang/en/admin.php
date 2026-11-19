<?php

// Content admin panel (Filament). Server-only: not shared with React.

return [
    'reimport_notice' => [
        'heading' => 'Panel edits are temporary',
        'body' => 'The next content import overwrites changes made here. Copy the row as JSON and update the matching file under content/ to keep them.',
    ],
    'exams' => [
        'placement_taken' => 'There is already an active placement exam. Archive it first or keep this exam a level exam.',
    ],
    'archived' => [
        'column' => 'State',
        'badge' => 'Archived',
        'filter' => 'Archived',
        'all' => 'All rows',
        'only' => 'Archived only',
        'active' => 'Active only',
    ],
    'actions' => [
        'back' => 'Back',
        'archive' => [
            'label' => 'Archive',
            'heading' => 'Archive this row?',
            'description' => 'It leaves the course for learners. Attempts and progress that point at it are kept, and you can restore it later.',
            'done' => 'Archived',
        ],
        'restore' => [
            'label' => 'Restore',
            'heading' => 'Restore this row?',
            'description' => 'It returns to the course for learners.',
            'done' => 'Restored',
        ],
        'copy_as_json' => [
            'label' => 'Copy as JSON',
            'heading' => 'Copy as JSON',
            'description' => 'The row by key, without database ids or timestamps. Paste it into the matching file under content/.',
            'close' => 'Close',
        ],
    ],
];
