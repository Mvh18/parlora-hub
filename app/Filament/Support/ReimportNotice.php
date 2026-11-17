<?php

namespace App\Filament\Support;

use Filament\Schemas\Components\Callout;

/**
 * The warning on every content edit form: the JSON under `content/` is the
 * source of truth, so the next import overwrites panel edits.
 */
final class ReimportNotice
{
    public static function make(): Callout
    {
        return Callout::make(__('admin.reimport_notice.heading'))
            ->description(__('admin.reimport_notice.body'))
            ->warning();
    }
}
