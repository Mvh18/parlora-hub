<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * Config values applied right after the config files load, before any
     * service provider registers. Set through bootWithConfig().
     *
     * @var array<string, mixed>
     */
    private array $bootConfig = [];

    /**
     * Same as the framework's createApplication(), plus the boot-time config
     * hook. The project does not use WithCachedConfig or WithCachedRoutes.
     */
    public function createApplication()
    {
        /** @var Application $app */
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $this->traitsUsedByTest = class_uses_recursive(static::class);

        $app->afterBootstrapping(LoadConfiguration::class, function (Application $app) {
            $app->make('config')->set($this->bootConfig);
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Rebuild the application with config that matters at boot time, such
     * as settings that decide which routes are registered. Changing the
     * environment does not work for this: Dotenv re-applies the .env values
     * it loaded for the first application.
     *
     * @param  array<string, mixed>  $config
     */
    protected function bootWithConfig(array $config): void
    {
        $this->bootConfig = $config;

        $this->refreshApplication();
        $this->withoutVite();
    }

    /**
     * PHP tests never need built front-end assets, so CI can run them without
     * `pnpm build`.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
