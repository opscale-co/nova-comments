<?php

declare(strict_types=1);

namespace Opscale\NovaComments\Tests;

use Illuminate\Foundation\Application;
use Inertia\ServiceProvider as InertiaServiceProvider;
use Laravel\Fortify\FortifyServiceProvider;
use Laravel\Nova\NovaCoreServiceProvider;
use Opscale\NovaComments\Models\Comment;
use Opscale\NovaComments\ToolServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Workbench\App\Providers\NovaServiceProvider as WorkbenchNovaServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../vendor/laravel/nova/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../workbench/database/migrations');
    }

    protected function tearDown(): void
    {
        Comment::whenCreating(null);

        parent::tearDown();
    }

    /**
     * @param  Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            InertiaServiceProvider::class,
            FortifyServiceProvider::class,
            NovaCoreServiceProvider::class,
            ToolServiceProvider::class,
            WorkbenchServiceProvider::class,
            WorkbenchNovaServiceProvider::class,
        ];
    }

    /**
     * @param  Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', \Workbench\App\Models\User::class);
    }
}
