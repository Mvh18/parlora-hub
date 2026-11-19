<?php

namespace App\Filament\Resources\Passages\Pages;

use App\Filament\Resources\Passages\PassageResource;
use App\Filament\Support\EditContentRecord;

class EditPassage extends EditContentRecord
{
    protected static string $resource = PassageResource::class;

    protected function parentUrl(): ?string
    {
        return PassageResource::getUrl('index');
    }
}
