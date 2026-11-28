<?php

namespace App\Http\Responses\Concerns;

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a signed-in user to the page they were heading to.
 *
 * The sign-in forms submit through Inertia, so a plain redirect is followed
 * by the Inertia XHR. When the destination is not an Inertia page (the
 * Filament panel or Horizon), Inertia would show its HTML in an error modal;
 * an Inertia location response makes the browser load it as a full page.
 */
trait RedirectsToIntendedPage
{
    protected function redirectToIntendedPage(Request $request): Response
    {
        $redirect = redirect()->intended(Fortify::redirects('login'));
        $url = $redirect->getTargetUrl();

        if ($request->header('X-Inertia') && $this->isServedOutsideInertia($url)) {
            return Inertia::location($url);
        }

        return $redirect;
    }

    private function isServedOutsideInertia(string $url): bool
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        foreach ($this->nonInertiaPaths() as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function nonInertiaPaths(): array
    {
        $paths = array_map(
            fn ($panel): string => trim($panel->getPath(), '/'),
            array_values(Filament::getPanels()),
        );

        $paths[] = trim(config()->string('horizon.path'), '/');

        return array_values(array_filter($paths, fn (string $path): bool => $path !== ''));
    }
}
