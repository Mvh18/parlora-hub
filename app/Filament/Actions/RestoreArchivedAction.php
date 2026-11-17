<?php

namespace App\Filament\Actions;

use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Brings an archived content row back into the course, authorizing the
 * `restore` ability. (Filament's own RestoreAction is for soft deletes, which
 * content does not use.)
 */
class RestoreArchivedAction extends ArchiveAction
{
    public static function getDefaultName(): ?string
    {
        return 'restore';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.actions.restore.label'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('gray')
            ->modalHeading(__('admin.actions.restore.heading'))
            ->modalDescription(__('admin.actions.restore.description'))
            ->authorize('restore')
            ->visible(fn (Model $record): bool => self::isArchived($record))
            ->successNotificationTitle(__('admin.actions.restore.done'))
            ->action(function (Model $record): void {
                self::ensureArchivable($record);

                if (method_exists($record, 'restore')) {
                    $record->restore();
                }

                $this->success();
            });
    }
}
