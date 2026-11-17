<?php

namespace App\Filament\Actions;

use App\Models\Concerns\Archivable;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Archives a content row: it leaves the course, but history that points at it
 * is kept. Filament does not check policies for custom actions, so this one
 * authorizes the `archive` ability itself.
 */
class ArchiveAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'archive';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.actions.archive.label'))
            ->icon(Heroicon::OutlinedArchiveBox)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('admin.actions.archive.heading'))
            ->modalDescription(__('admin.actions.archive.description'))
            ->authorize('archive')
            ->visible(fn (Model $record): bool => ! self::isArchived($record))
            ->successNotificationTitle(__('admin.actions.archive.done'))
            ->action(function (Model $record): void {
                self::ensureArchivable($record);

                if (method_exists($record, 'archive')) {
                    $record->archive();
                }

                $this->success();
            });
    }

    protected static function isArchived(Model $record): bool
    {
        self::ensureArchivable($record);

        return method_exists($record, 'isArchived') && $record->isArchived();
    }

    /**
     * @throws LogicException when the row's model does not use Archivable
     */
    protected static function ensureArchivable(Model $record): void
    {
        if (! in_array(Archivable::class, class_uses_recursive($record), true)) {
            throw new LogicException(sprintf('%s is not archivable.', $record::class));
        }
    }
}
