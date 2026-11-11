<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
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
            $app->make('config')->set([
                // Feature tests assert client-rendered Inertia responses. With
                // SSR on, every page render posts to the Vite dev server
                // (`/__inertia_ssr`) when `public/hot` exists.
                'inertia.ssr.enabled' => false,
                ...$this->bootConfig,
            ]);
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
        Http::preventStrayRequests();
    }

    /**
     * PHP tests never need built front-end assets, so CI can run them without
     * `pnpm build`. No test may reach the network through the HTTP client
     * (Parlora Tutor included): a test that needs a response fakes it with
     * Http::fake().
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Http::preventStrayRequests();
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
