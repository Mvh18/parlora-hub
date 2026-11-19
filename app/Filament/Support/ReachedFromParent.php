<?php

namespace App\Filament\Support;

use App\Filament\Resources\Levels\LevelResource;
use Filament\Resources\Resource as FilamentResource;
use Illuminate\Database\Eloquent\Model;

/**
 * For content resources that have an edit page but no list of their own
 * (units, lessons, exam sections, exercises, ...): they are reached by drilling
 * down from a top-level resource, so Filament's "index" links (breadcrumbs) go
 * to that resource's list instead of a page that does not exist. The levels
 * list is the default; a resource overrides `indexResource()` for another.
 */
trait ReachedFromParent
{
    /** @return class-string<FilamentResource> */
    protected static function indexResource(): string
    {
        return LevelResource::class;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return static::indexResource()::getUrl('index', [], $isAbsolute, $panel, $tenant);
    }
}
