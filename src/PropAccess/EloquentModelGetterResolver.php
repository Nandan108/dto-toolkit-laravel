<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\PropAccess;

use Illuminate\Database\Eloquent\Model;
use Nandan108\PropAccess\Contract\GetterMapResolverInterface;
use Nandan108\PropAccess\Exception\AccessorException;

/**
 * @implements GetterMapResolverInterface<non-falsy-string>
 */
final class EloquentModelGetterResolver implements GetterMapResolverInterface
{
    /** @var RelationAccessPolicy|callable(Model, string): mixed */
    private static mixed $handleRelationAccess = RelationAccessPolicy::ALLOW_NONE;

    /**
     * Set the relation access handler.
     *
     * WARNING: This setting is meant to be global and static, decided at application boot time.
     * It is NOT meant to be changed at runtime, and changing it will resset the internal cache.
     *
     * @param RelationAccessPolicy|callable(Model $model, string $relationName): mixed $handler
     */
    public static function setRelationAccessHandler(RelationAccessPolicy | callable $handler): void
    {
        if (self::$handleRelationAccess !== $handler) {
            // Clear cache when changing handler, to avoid stale closures with old handler logic
            EloquentModelResolverCache::clear();
            self::$handleRelationAccess = $handler;
        }
    }

    /**
     * @return RelationAccessPolicy|callable(Model $model, string $relationName): mixed
     */
    public static function getRelationAccessHandler(): RelationAccessPolicy | callable
    {
        return self::$handleRelationAccess;
    }

    /*
    |--------------------------------------------------------------------------
    | Contract
    |--------------------------------------------------------------------------
    |
    | These methods implement the GetterMapResolverInterface contract.
    |
    */

    #[\Override]
    public function supports(mixed $value): bool
    {
        return $value instanceof Model;
    }

    /**
     * @param array<array-key>|non-falsy-string|null $propNames
     *
     * @return array<truthy-string, \Closure(mixed): mixed>
     */
    #[\Override]
    public function getGetterMap(
        mixed $valueSource,
        array | string | null $propNames = null,
        bool $ignoreInaccessibleProps = true,
    ): array {
        if (!$valueSource instanceof Model) {
            return [];
        }

        $meta = $this->getMeta($valueSource);
        $propNames = $this->normalizePropNames($valueSource, $meta, $propNames);

        /** @var array<truthy-string, \Closure(Model): mixed> $getters */
        $getters = [];

        foreach ($propNames as $name) {
            if ($meta->isRelation($name)) {
                $getters[$name] = $this->buildRelationGetter($name);
                continue;
            }

            // Eloquent delegates attribute and dynamic access via __get, so non-relation
            // properties are readable by default.
            $getters[$name] = $this->buildAttributeGetter($name);
        }

        return $getters;
    }

    /*
    |--------------------------------------------------------------------------
    | Getter Builders
    |--------------------------------------------------------------------------
    */

    /**
     * Build a getter for a regular attribute, which simply accesses the property on the model.
     * Note: Eloquent models handle dynamic attribute access via __get, so we can rely on that for non-relation properties.
     *
     * @return \Closure(Model): mixed
     */
    private function buildAttributeGetter(string $name): \Closure
    {
        return fn (Model $model): mixed => $model->$name;
    }

    /**
     * Build a getter for a relation property, which resolves the relation according to the configured access policy.
     *
     * @return \Closure(Model): mixed
     */
    private function buildRelationGetter(string $name): \Closure
    {
        return fn (Model $model): mixed => $this->resolveRelationAccess($model, $name);
    }

    /*
    |--------------------------------------------------------------------------
    | Relation Resolution
    |--------------------------------------------------------------------------
    */

    private function resolveRelationAccess(Model $model, string $name): mixed
    {
        $handler = self::$handleRelationAccess;

        if ($handler instanceof RelationAccessPolicy) {
            return match ($handler) {
                RelationAccessPolicy::ALLOW_NONE => throw new AccessorException(
                    'Relation access is disabled by current policy. '
                    ."Attempted to access relation \"$name\" on model ".$model::class.'.',
                ),

                RelationAccessPolicy::ALLOW_LOADED => $this->resolveLoadedOnly($model, $name),

                RelationAccessPolicy::ALLOW_LAZY =>
                    // WARNING: This triggers native lazy loading
                    $model->$name,
            };
        }

        // Custom callable handler
        return $handler($model, $name);
    }

    private function resolveLoadedOnly(Model $model, string $name): mixed
    {
        if (!$model->relationLoaded($name)) {
            throw new AccessorException(
                'Relation "'.$name.'" is not loaded on model '.$model::class.'.',
            );
        }

        // Important: do NOT use $model->$name here
        return $model->getRelation($name);
    }

    /*
    |--------------------------------------------------------------------------
    | Metadata
    |--------------------------------------------------------------------------
    */

    private function getMeta(Model $model): EloquentModelMeta
    {
        return EloquentModelResolverCache::for($model);
    }

    /*
    |--------------------------------------------------------------------------
    | Property Name Normalization
    |--------------------------------------------------------------------------
    */

    /**
     * Summary of normalizePropNames.
     *
     * @param array<array-key>|truthy-string|null $propNames
     *
     * @return list<truthy-string>
     */
    private function normalizePropNames(Model $model, EloquentModelMeta $meta, array | string | null $propNames): array
    {
        if (is_string($propNames)) {
            return [$propNames];
        }

        if (is_array($propNames)) {
            /** @var list<truthy-string> */
            return array_filter($propNames);
        }

        // If null → default strategy
        // We do NOT attempt to enumerate all model attributes,
        // because Eloquent does not expose a canonical list of
        // readable properties without touching state.
        //
        // Therefore, when null, we return:
        // - array_keys($model->getAttributes())
        // - plus loaded relations (optional)
        //
        // This can be tuned based on desired behavior.

        /** @var list<truthy-string> */
        return array_values(array_filter(array_unique([
            ...array_keys($model->getAttributes()),
            ...array_keys($model->getRelations()),
            ...array_keys($meta->relations),
            ...array_keys($meta->hasGetter),
            ...array_keys($meta->columns),
        ])));
    }
}
