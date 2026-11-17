<?php

namespace Tests\Support\Admin;

use Closure;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * A bare Livewire form over one record, so the panel's form building blocks
 * can be tested the way an edit page uses them (fill, validate, save) before
 * any resource exists. Set the schema with RecordForm::using().
 */
class RecordForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var (Closure(): list<mixed>)|null */
    private static ?Closure $components = null;

    public Model $record;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** @param  Closure(): list<mixed>  $components */
    public static function using(Closure $components): void
    {
        self::$components = $components;
    }

    public function mount(Model $record): void
    {
        $this->record = $record;
        $this->form->fill($record->attributesToArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components((self::$components ?? fn (): array => [])())
            ->statePath('data')
            ->model($this->record);
    }

    public function save(): void
    {
        $this->record->update($this->form->getState());
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
