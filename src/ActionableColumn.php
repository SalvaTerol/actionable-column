<?php

namespace Shreejan\ActionableColumn;

use Filament\Contracts\Plugin;
use Filament\Panel;

class ActionableColumn implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'actionable-column';
    }

    public function register(Panel $panel): void
    {
        // TODO: Implement register() method.
    }

    public function boot(Panel $panel): void
    {
        // TODO: Implement boot() method.
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
