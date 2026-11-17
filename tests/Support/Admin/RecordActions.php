<?php

namespace Tests\Support\Admin;

use App\Filament\Actions\ArchiveAction;
use App\Filament\Actions\CopyAsJsonAction;
use App\Filament\Actions\RestoreArchivedAction;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * Hosts the shared row actions on one record so they can be called through
 * Livewire, as a panel page will, before any resource exists.
 */
class RecordActions extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public Model $record;

    public function archiveAction(): Action
    {
        return ArchiveAction::make()->record($this->record);
    }

    public function restoreAction(): Action
    {
        return RestoreArchivedAction::make()->record($this->record);
    }

    public function copyAsJsonAction(): Action
    {
        return CopyAsJsonAction::make()->record($this->record);
    }

    public function render(): string
    {
        return '<div>{{ $this->archiveAction }} {{ $this->restoreAction }} {{ $this->copyAsJsonAction }} <x-filament-actions::modals /></div>';
    }
}
