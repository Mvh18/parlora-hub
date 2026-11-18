<?php

namespace App\Filament\Support;

use App\Filament\Actions\ArchiveAction;
use App\Filament\Actions\CopyAsJsonAction;
use App\Filament\Actions\RestoreArchivedAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * The edit page of every content row: Copy as JSON, archive and restore in the
 * header (and a link back to the parent), and no delete action. Translatable
 * attributes are loaded as their per-locale arrays, which is what the form's
 * `<attribute>.<locale>` fields bind to.
 */
abstract class EditContentRecord extends EditRecord
{
    /** Where "Back" goes: the parent's edit page, or null for none. */
    protected function parentUrl(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        $url = $this->parentUrl();

        return array_values(array_filter([
            $url === null ? null : Action::make('back')
                ->label(__('admin.actions.back'))
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url($url),
            CopyAsJsonAction::make(),
            ArchiveAction::make(),
            RestoreArchivedAction::make(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        if (method_exists($record, 'getTranslatableAttributes') && method_exists($record, 'getTranslations')) {
            foreach ($record->getTranslatableAttributes() as $attribute) {
                $data[$attribute] = $record->getTranslations($attribute);
            }
        }

        return $data;
    }
}
