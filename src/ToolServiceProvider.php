<?php

declare(strict_types=1);

namespace Opscale\NovaComments;

use Opscale\NovaComments\Nova\Comment;
use Opscale\NovaPackageTools\NovaPackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ToolServiceProvider extends NovaPackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('nova-comments')
            ->hasConfigFile('nova-comments')
            ->hasTranslations()
            ->discoversMigrations()
            ->runsMigrations()
            ->hasResource(Comment::class)
            ->hasNovaAssets('nova-comments', __DIR__ . '/../dist');
    }
}
