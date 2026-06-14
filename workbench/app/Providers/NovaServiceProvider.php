<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Dashboards\Main;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Workbench\App\Nova\Post;
use Workbench\App\Nova\User;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * @return array<int, mixed>
     */
    public function tools(): array
    {
        return [];
    }

    protected function resources(): void
    {
        Nova::resources([
            User::class,
            Post::class,
        ]);
    }

    protected function gate(): void
    {
        Gate::define('viewNova', fn ($user): bool => true);
    }

    /**
     * @return array<int, mixed>
     */
    protected function dashboards(): array
    {
        return [
            new Main,
        ];
    }
}
