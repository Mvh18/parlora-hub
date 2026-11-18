<?php

namespace App\Filament\Resources\Levels\Pages;

use App\Filament\Resources\Levels\LevelResource;
use App\Filament\Support\EditContentRecord;

class EditLevel extends EditContentRecord
{
    protected static string $resource = LevelResource::class;

    protected function parentUrl(): ?string
    {
        return LevelResource::getUrl('index');
    }
}
