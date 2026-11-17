<?php

namespace App\Filament\Actions;

use App\Filament\Support\ContentJson;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

/**
 * Shows the row as key-based JSON (ContentJson) in a read-only, copyable
 * monospace block, so a panel touch-up can be carried into `content/`.
 * Filament's CodeEntry would add highlighting but needs the optional
 * phiki/phiki package. The export holds solutions and transcripts, so the
 * action authorizes `view` explicitly.
 */
class CopyAsJsonAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'copyAsJson';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('admin.actions.copy_as_json.label'))
            ->icon(Heroicon::OutlinedClipboardDocument)
            ->color('gray')
            ->authorize('view')
            ->modalHeading(__('admin.actions.copy_as_json.heading'))
            ->modalDescription(__('admin.actions.copy_as_json.description'))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('admin.actions.copy_as_json.close'))
            ->schema([
                TextEntry::make('json')
                    ->hiddenLabel()
                    ->state(fn (Model $record): string => ContentJson::encode($record))
                    ->fontFamily(FontFamily::Mono)
                    ->extraAttributes(['style' => 'white-space: pre; overflow-x: auto'])
                    ->copyable(),
            ]);
    }
}
