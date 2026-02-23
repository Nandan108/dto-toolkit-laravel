<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel;

use Illuminate\Support\ServiceProvider;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Laravel\PropAccess as LaravelPropAccess;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use Nandan108\PropAccess\PropAccess;

/** @psalm-suppress UnusedClass */
final class DtoToolkitServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        // Config
        $this->mergeConfigFrom(
            __DIR__.'/../config/dto-toolkit.php',
            'dto-toolkit',
        );

        // - Container bridge - plug Laravel's container into DTOT
        ContainerBridge::setContainer($this->app);

        // register BaseDto in the container
        $this->app->resolving(BaseDto::class, BaseDto::new(...));

        // TODO: Bindings will go here :
        // - Error translator
        // - Exception mapper
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config/dto-toolkit.php' => config_path('dto-toolkit.php'),
        ], 'dto-toolkit-config');

        // Publish translations
        $this->loadTranslationsFrom(
            __DIR__.'/../resources/lang',
            'dto-toolkit',
        );

        // Set default load request include buckets based on config, with sensible defaults
        /** @psalm-var mixed */
        $requestLoadInclude = config('dto-toolkit.request_load_include', FullDto::getDefaultLoadRequestInclude());
        if (!\is_array($requestLoadInclude)) {
            $requestLoadInclude = FullDto::getDefaultLoadRequestInclude();
        }
        FullDto::setDefaultLoadRequestInclude(
            array_values(array_map(static fn (mixed $bucket): string => (string) $bucket, $requestLoadInclude)),
        );

        // Initialize PropAccess with default adapters
        PropAccess::bootDefaultResolvers();
        // Register Laravel-specific resolvers for Eloquent models
        PropAccess::registerResolvers([
            new LaravelPropAccess\EloquentModelGetterResolver(),
            new LaravelPropAccess\EloquentModelSetterResolver(),
        ]);

        // Set write policy for unknown attributes based on config, with sensible defaults for dev vs prod
        $debug = (bool) config('app.debug', false);
        $defaultWritePolicy = $debug
            ? LaravelPropAccess\WriteUnknownAttrPolicy::SCHEMA_VERIFIED // perf hit in dev, but allows catching mistakes early
            : LaravelPropAccess\WriteUnknownAttrPolicy::ALLOW_UNKNOWN; // best performance in prod, typos = failed DB writes, which are caught by tests and CI
        /** @var string $writePolicy */
        $writePolicy = config('dto-toolkit.write_unknown_attr_policy', $defaultWritePolicy->value);
        LaravelPropAccess\EloquentModelSetterResolver::$writePolicy = LaravelPropAccess\WriteUnknownAttrPolicy::from($writePolicy);

        // Set relation access handler based on config, with sensible default of allowing only loaded relations to prevent N+1 issues
        /** @var string $relationAccessPolicy */
        $relationAccessPolicy = config('dto-toolkit.relation_access', LaravelPropAccess\RelationAccessPolicy::ALLOW_LOADED->value);
        // Note: setRelationAccessHandler accepts either a pre-set policy or a custom callable
        LaravelPropAccess\EloquentModelGetterResolver::setRelationAccessHandler(
            // from() call will fail fast if invalid config value is provided, that's fine!
            LaravelPropAccess\RelationAccessPolicy::from($relationAccessPolicy),
        );
    }
}
