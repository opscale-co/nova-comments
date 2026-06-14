<?php

declare(strict_types=1);

namespace Workbench\App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Workbench\App\Models\User;

class WorkbenchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // workbench-only bindings
    }

    public function boot(): void
    {
        config(['auth.providers.users.model' => User::class]);

        Gate::define('viewNova', fn (?Authenticatable $user): bool => true);

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
