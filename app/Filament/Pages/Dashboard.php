<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as FilamentDashboard;

/**
 * The panel's landing page. It stays reachable at `/admin` but is not a
 * navigation entry: the panel lists only Levels, Passages and Exams.
 */
class Dashboard extends FilamentDashboard
{
    protected static bool $shouldRegisterNavigation = false;
}
