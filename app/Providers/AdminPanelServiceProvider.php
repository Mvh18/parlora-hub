<?php

namespace App\Providers;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\AuthenticateContentPanel;
use App\Models\User;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelServiceProvider extends PanelProvider
{
    /**
     * Who may open the content panel: a verified admin whose two-factor
     * authentication is confirmed. A passkey sign-in counts the same; the rule
     * checks that 2FA is configured, not how the session was opened. The panel
     * shows solutions and transcripts, so this is the security boundary.
     */
    public function boot(): void
    {
        Gate::define('accessContentPanel', fn (User $user): bool => $user->is_admin
            && $user->hasVerifiedEmail()
            && $user->two_factor_confirmed_at !== null);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->colors(['primary' => Color::hex('#3550E6')])
            ->font('Figtree Variable', url: fn (): string => asset('fonts/figtree/figtree.css'), provider: LocalFontProvider::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->pages([Dashboard::class])
            // A missing policy method throws instead of allowing the action.
            ->strictAuthorization()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                // Ends panel sessions on other devices once the password changes.
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                AuthenticateContentPanel::class,
            ], isPersistent: true);
    }
}
