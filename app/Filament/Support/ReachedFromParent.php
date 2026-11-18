<?php

namespace App\Filament\Support;

use App\Filament\Resources\Levels\LevelResource;
use Illuminate\Database\Eloquent\Model;

/**
 * For content resources that have an edit page but no list of their own
 * (units, lessons, vocabulary items, exercises): they are reached by drilling
 * down from a level, so Filament's "index" links (breadcrumbs) go to the
 * levels list instead of a page that does not exist.
 */
trait ReachedFromParent
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return LevelResource::getUrl('index', [], $isAbsolute, $panel, $tenant);
    }
}
