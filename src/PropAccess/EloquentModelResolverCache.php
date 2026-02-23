<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\PropAccess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class EloquentModelResolverCache
{
    /** @var array<string, EloquentModelMeta> */
    private static array $cache = [];

    /**
     * A cache for schema columns of Eloquent models, by connection and table name.
     *
     * @var array<string, array<string, array<string, true>>>
     */
    private static array $schemaCache = [];

    public static function clear(): void
    {
        self::$cache = [];
        self::$schemaCache = [];
    }

    public static function for(Model $model): EloquentModelMeta
    {
        return self::$cache[self::cacheKey($model)] ??= self::build($model);
    }

    private static function cacheKey(Model $model): string
    {
        if (WriteUnknownAttrPolicy::SCHEMA_VERIFIED !== EloquentModelSetterResolver::$writePolicy) {
            return $model::class;
        }

        /** @var string */
        $connection = $model->getConnectionName() ?? config('database.default');

        return implode('|', [
            $model::class,
            $connection,
            $model->getTable(),
        ]);
    }

    /**
     * Build the metadata for a given Eloquent model class.
     */
    private static function build(Model $modelInstance): EloquentModelMeta
    {
        $meta = new EloquentModelMeta($modelInstance::class);
        $modelInstance->getAttributes();

        $publicMethods = self::getPublicMethodsWithTypes($meta);
        self::collectRelations($meta, $publicMethods);
        self::collectAccessorsAndMutators($meta, $publicMethods);

        // Depending on current policy, which should depend on dev/prod mode, we'll load
        // schema information to get field names and

        if (WriteUnknownAttrPolicy::SCHEMA_VERIFIED === EloquentModelSetterResolver::$writePolicy) {
            self::collectSchemaColumns($meta, $modelInstance);
        }

        // First collect properties from $fillable, $guarded and $casts
        // self::collectListed($meta, $modelInstance);

        // Then analyze public methods for relations and accessors/mutators

        return $meta;
    }

    private static function collectSchemaColumns(
        EloquentModelMeta $meta,
        Model $model,
    ): void {
        /** @var string */
        $connection = $model->getConnectionName() ?? config('database.default');
        $table = $model->getTable();

        if (!isset(self::$schemaCache[$connection][$table])) {
            /** @var list<string> $columns */
            $columns = $model
                ->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($table);

            self::$schemaCache[$connection][$table] = array_fill_keys($columns, true);
        }

        $meta->columns = self::$schemaCache[$connection][$table];
    }

    /**
     * Get all public methods of the class that have a single return type.
     * The return type is used to identify potential relation methods and attribute accessors/mutators.
     *
     *
     * @return array<int, array{method: \ReflectionMethod, returnType: string}>
     */
    private static function getPublicMethodsWithTypes(EloquentModelMeta $meta): array
    {
        $publicMethods = $meta->ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Note: foreignKey resolution can happen lazily during setter execution by invoking
        // the relation instance. No need to compute it at reflection time.
        $methodsWithTypes = [];
        foreach ($publicMethods as $methodRef) {

            if ($methodRef->isStatic()) {
                continue;
            }

            if ($methodRef->getNumberOfParameters() > 0) {
                continue;
            }

            $returnType = $methodRef->getReturnType();
            if (!$returnType instanceof \ReflectionNamedType) {
                continue;
            }

            $typeName = $returnType->getName();
            $methodsWithTypes[] = [
                'method'     => $methodRef,
                'returnType' => $typeName,
            ];
        }

        return $methodsWithTypes;
    }

    /** @param array<int, array{method: \ReflectionMethod, returnType: string}> $publicMethods */
    private static function collectRelations(
        EloquentModelMeta $meta,
        array $publicMethods,
    ): void {
        foreach ($publicMethods as ['method' => $methodRef, 'returnType' => $typeName]) {
            if (!is_subclass_of($typeName, Relation::class)) {
                continue;
            }

            $relationMeta = new RelationMeta(
                method: $methodRef,
                relationClass: $typeName,
            );

            if ($relationMeta->setterSupported) {
                $meta->hasSetter[$relationMeta->name] = true;
            }

            $meta->relations[$relationMeta->name] = $relationMeta;
        }
    }

    /** @param array<int, array{method: \ReflectionMethod, returnType: string}> $publicMethods */
    private static function collectAccessorsAndMutators(
        EloquentModelMeta $meta,
        array $publicMethods,
    ): void {
        foreach ($publicMethods as ['method' => $method, 'returnType' => $typeName]) {

            $name = $method->getName();

            if (preg_match('/^get(.+)Attribute$/', $name, $m)) {
                $prop = lcfirst($m[1]);
                $meta->hasGetter[$prop] = true;
            }

            if (preg_match('/^set(.+)Attribute$/', $name, $m)) {
                $prop = lcfirst($m[1]);
                $meta->hasSetter[$prop] = true;
            }

            // Modern Attribute::make detection
            // If return type is Illuminate\Database\Eloquent\Casts\Attribute
            if (\Illuminate\Database\Eloquent\Casts\Attribute::class === $typeName) {
                $prop = $method->getName();
                $meta->hasGetter[$prop] = true;
                $meta->hasSetter[$prop] = true; // safe assumption
            }
        }
    }
}
