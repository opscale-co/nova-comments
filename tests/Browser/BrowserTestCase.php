<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests\Browser;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Nova\NovaCoreServiceProvider;
use Opscale\NovaComments\ToolServiceProvider;
use Orchestra\Testbench\Dusk\TestCase as DuskTestCase;
use Workbench\App\Providers\NovaServiceProvider as WorkbenchNovaServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class BrowserTestCase extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../../workbench/database/migrations');
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            NovaCoreServiceProvider::class,
            ToolServiceProvider::class,
            WorkbenchServiceProvider::class,
            WorkbenchNovaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', \Workbench\App\Models\User::class);
        $app['config']->set('nova-comments.commenter.nova-resource', \Workbench\App\Nova\User::class);
    }

    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments([
            '--disable-gpu',
            '--headless=new',
            '--no-sandbox',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options),
        );
    }
}
