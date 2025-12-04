<?php

namespace Shreejan\ActionableColumn;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ActionableColumnServiceProvider extends PackageServiceProvider
{
    public static string $name = 'actionable-column';

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
                    id: 'actionable-column',
                    path: __DIR__.'/../resources/dist/css/actionable-column.css'
                ),
            ],
            package: 'shreejan/actionable-column'
        );
    }
}