<?php

namespace Shreejan\ActionableColumn;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\File;
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

        // Register default CSS first
        $assets = [
            Css::make('actionable-column', __DIR__.'/../resources/dist/css/actionable-column.css'),
        ];

        // Register custom CSS if provided (loads after default, allowing overrides)
        // This solves the issue where composer install overwrites modified CSS in public/css/
        // Custom CSS in resources/css/ won't be overwritten and loads after default CSS
        $customCssPath = $this->getCustomCssPath();

        if ($customCssPath && File::exists($customCssPath)) {
            $assets[] = Css::make('actionable-column-custom', $customCssPath);
        }

        FilamentAsset::register($assets, package: 'shreejan/actionable-column');
    }

    protected function getCustomCssPath(): ?string
    {
        $configPath = config('actionable-column.custom_css_path');
        if (! empty($configPath)) {
            return str_starts_with($configPath, '/') ? $configPath : base_path($configPath);
        }

        $defaultPath = resource_path('css/actionable-column-custom.css');
        return File::exists($defaultPath) ? $defaultPath : null;
    }
}