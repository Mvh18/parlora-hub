<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToIntendedPage;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    use RedirectsToIntendedPage;

    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        return $request->wantsJson()
            ? response()->json(['two_factor' => false])
            : $this->redirectToIntendedPage($request);
    }
}
