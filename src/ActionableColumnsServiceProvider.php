<?php

namespace Shreejan\FilamentActionableColumns;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ActionableColumnsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-actionable-columns';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile();
    }

    public function boot(): void
    {
        parent::boot();

    }
}