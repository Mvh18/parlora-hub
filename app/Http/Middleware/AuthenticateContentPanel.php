<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Filament's authentication middleware with Hub's login as the guest
 * destination: the panel has no login page, so guests go to Fortify's, which
 * keeps the intended URL and returns to the panel afterwards.
 */
class AuthenticateContentPanel extends Authenticate
{
    public const FORBIDDEN_MESSAGE = 'The content panel requires an admin account with a verified email and two-factor authentication.';

    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        try {
            parent::authenticate($request, $guards);
        } catch (HttpException $exception) { // @phpstan-ignore catch.neverThrown (the parent calls abort_if(…, 403))
            if ($exception->getStatusCode() !== 403) {
                throw $exception;
            }

            throw new HttpException(403, self::FORBIDDEN_MESSAGE, $exception);
        }
    }

    protected function redirectTo($request): string
    {
        return route('login');
    }
}
