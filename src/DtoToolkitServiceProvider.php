<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel;

use Illuminate\Support\ServiceProvider;

final class DtoToolkitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/dto-toolkit.php',
            'dto-toolkit'
        );

        // TODO: Bindings will go here (later):
        // - Container bridge
        // - Error translator
        // - Exception mapper
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../config/dto-toolkit.php' => config_path('dto-toolkit.php'),
        ], 'dto-toolkit-config');

        // Publish translations
        $this->loadTranslationsFrom(
            __DIR__ . '/../resources/lang',
            'dto-toolkit'
        );

        // TODO: Prop-access & Eloquent adapters will be booted here later
    }
}
