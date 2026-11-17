<?php

use App\Providers\AdminPanelServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelServiceProvider::class,
    FortifyServiceProvider::class,
    HorizonServiceProvider::class,
];
