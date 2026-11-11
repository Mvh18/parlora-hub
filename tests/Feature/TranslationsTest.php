<?php

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

/*
 * All UI copy lives in lang/<locale>/*.php. HandleInertiaRequests shares the
 * active locale's groups with React, which reads them through useTrans().
 */

/**
 * @return array<string, mixed>
 */
function sharedProps(mixed $page): array
{
    return is_array($page) ? $page['props'] : [];
}

test('an authenticated page shares the locale and the shell translations', function () {
    $props = sharedProps($this->actingAs(User::factory()->create())
        ->get(route('home'))->assertOk()->viewData('page'));

    expect($props['locale'])->toBe('en')
        ->and($props['translations'])->toHaveKeys(['app', 'auth', 'nav', 'settings'])
        ->and($props['translations']['nav']['home'])->toBe(__('nav.home'));
});

test('guest pages share the translations too', function () {
    $props = sharedProps($this->get(route('login'))->assertOk()->viewData('page'));

    expect($props['translations']['auth']['login']['title'])->toBe(__('auth.login.title'));
});

test('server-only groups are not shared', function () {
    $props = sharedProps($this->get(route('login'))->viewData('page'));

    expect($props['translations'])->not->toHaveKeys(['validation', 'pagination', 'passwords']);
});

test('the translations prop is resolved once per locale', function () {
    $page = $this->get(route('login'))->viewData('page');

    expect($page['onceProps'] ?? [])->toHaveKey('translations.en');
});

test('every translation key used by the front end exists', function () {
    $groups = collect(File::files(lang_path('en')))
        ->map(fn (SplFileInfo $file) => $file->getBasename('.php'))
        ->implode('|');

    $keys = collect(File::allFiles(resource_path('js')))
        // Tests and their support code (resources/js/test) are not front-end copy.
        ->reject(fn (SplFileInfo $file) => str_contains($file->getFilename(), '.test.')
            || str_starts_with(str_replace('\\', '/', $file->getRelativePath()), 'test'))
        ->flatMap(function (SplFileInfo $file) use ($groups) {
            preg_match_all("/['\"`]((?:{$groups})\\.[a-z0-9_]+(?:\\.[a-z0-9_]+)*)['\"`]/", $file->getContents(), $matches);

            return $matches[1];
        })
        ->unique()
        ->values();

    expect($keys)->not->toBeEmpty();

    $missing = $keys->reject(fn (string $key) => Lang::has($key, 'en', false))->values()->all();

    expect($missing)->toBe([]);
});

test('the linter rejects hard-coded copy in JSX', function () {
    expect((string) file_get_contents(base_path('vite.config.ts')))
        ->toMatch("/'react\/jsx-no-literals': \[\s*'error'/");
});
