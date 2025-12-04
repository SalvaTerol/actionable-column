<?php

namespace Shreejan\FilamentActionableColumns;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
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

    public function packageBooted(): void
    {
        parent::packageBooted();

        FilamentAsset::register(
            assets: [
                Css::make(
                    id: 'filament-actionable-columns',
                    path: __DIR__.'/../resources/dist/css/actionable-columns.css'
                ),
            ],
            package: 'shreejan/filament-actionable-columns'
        );
    }
}