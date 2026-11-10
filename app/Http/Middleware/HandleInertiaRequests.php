<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Inertia\Inertia;
use Inertia\Middleware;
use Laravel\Fortify\Features;

class HandleInertiaRequests extends Middleware
{
    /**
     * Lang groups that only the server reads: validation messages reach the
     * browser already translated, as form errors.
     */
    private const SERVER_ONLY_GROUPS = ['pagination', 'passwords', 'validation'];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'features' => [
                'registration' => Features::enabled(Features::registration()),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'locale' => $locale,
            // Sent on the first visit only: the client keeps it across
            // navigations until the locale, and with it the key, changes.
            'translations' => Inertia::once(fn () => $this->translations($locale))
                ->as("translations.{$locale}"),
        ];
    }

    /**
     * Every lang group of the locale, except those only the server reads.
     *
     * @return array<string, mixed>
     */
    private function translations(string $locale): array
    {
        $translations = [];

        foreach (glob(lang_path("{$locale}/*.php")) ?: [] as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);

            if (! in_array($group, self::SERVER_ONLY_GROUPS, true)) {
                $translations[$group] = Lang::get($group, [], $locale);
            }
        }

        return $translations;
    }
}
