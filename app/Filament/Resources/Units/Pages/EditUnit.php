<?php

namespace App\Filament\Resources\Units\Pages;

use App\Filament\Resources\Levels\LevelResource;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Support\EditContentRecord;
use App\Models\Unit;

class EditUnit extends EditContentRecord
{
    protected static string $resource = UnitResource::class;

    protected function parentUrl(): ?string
    {
        /** @var Unit $unit */
        $unit = $this->getRecord();

        return LevelResource::getUrl('edit', ['record' => $unit->level_id]);
    }
}
