<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Only admins may open Horizon, in every environment. Horizon's default
     * callback lets anyone in when APP_ENV=local, so it is replaced.
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(fn (Request $request) => Gate::check('viewHorizon', [$request->user()]));
    }

    /**
     * Register the Horizon gate.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', fn (?User $user = null) => $user?->is_admin === true);
    }
}
