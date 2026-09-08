<?php

declare(strict_types=1);

namespace Nsumbadze\WhereUsed;

use Nsumbadze\WhereUsed\Commands\CacheCommand;
use Nsumbadze\WhereUsed\Commands\ClearCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WhereUsedServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-where-used';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasCommands([
                CacheCommand::class,
                ClearCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ReferenceMap::class);
        $this->app->singleton(References::class);
    }
}
