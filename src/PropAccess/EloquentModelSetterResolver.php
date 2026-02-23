<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\PropAccess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Nandan108\DtoToolkit\Laravel\Exception\InvalidRelationValueException;
use Nandan108\PropAccess\Contract\SetterMapResolverInterface;
use Nandan108\PropAccess\Exception\AccessorException;

/** @implements SetterMapResolverInterface<non-falsy-string> */
final class EloquentModelSetterResolver implements SetterMapResolverInterface
{
    public static WriteUnknownAttrPolicy $writePolicy = WriteUnknownAttrPolicy::ALLOW_UNKNOWN;

    #[\Override]
    public function supports(mixed $value): bool
    {
        return $value instanceof Model;
    }

    /**
     * @return array<non-falsy-string, \Closure(mixed, mixed): void>
     *
     * @throws AccessorException if any requested property is not writable and $ignoreInaccessibleProps is false
     */
    #[\Override]
    public function getSetterMap(
        mixed $target,
        array | string | null $propNames = null,
        bool $ignoreInaccessibleProps = true,
    ): array {
        /** @var Model $target */
        $meta = EloquentModelResolverCache::for($target);
        $propNames = $this->normalizePropNames($target, $meta, $propNames);

        $setters = [];
        $inaccessible = [];

        foreach ($propNames as $name) {
            if ($relationMeta = $meta->relations[$name] ?? null) {
                if (!$relationMeta->setterSupported) {
                    $inaccessible[] = $name;
                    continue;
                }

                $setters[$name] = $this->buildRelationSetter($relationMeta);
                continue;
            }

            if ($meta->hasSetter($name)) {
                $setters[$name] = self::buildAttributeSetter($name);
                continue;
            }

            switch (self::$writePolicy) {
                case WriteUnknownAttrPolicy::ALLOW_UNKNOWN:
                    $setters[$name] = self::buildAttributeSetter($name);
                    continue 2;

                case WriteUnknownAttrPolicy::DECLARED_ONLY:
                    $inaccessible[] = $name;
                    continue 2;

                case WriteUnknownAttrPolicy::SCHEMA_VERIFIED:
                    if ($meta->isColumn($name)) {
                        $setters[$name] = self::buildAttributeSetter($name);
                    } else {
                        $inaccessible[] = $name;
                    }
                    continue 2;
            }
        }

        if ([] !== $inaccessible && !$ignoreInaccessibleProps) {
            throw new AccessorException(
                'No writable Eloquent property found for: '.implode(', ', $inaccessible).' in '.$target::class,
            );
        }

        return $setters;
    }

    /** @return \Closure(mixed, mixed): void */
    private static function buildAttributeSetter(string $name): \Closure
    {
        return static function (Model $model, mixed $value) use ($name): void {
            $model->$name = $value;
        };
    }

    /**
     * Normalize the requested property names. If null, we will attempt to infer all possible properties from the model's
     * attributes, relations, and meta information.
     *
     * @return list<non-falsy-string>
     */
    private function normalizePropNames(Model $target, EloquentModelMeta $meta, array | string | null $propNames): array
    {
        if (is_string($propNames)) {
            $propNames = [$propNames];
        }

        if (null === $propNames) {
            /** @var list<string> */
            $propNames = [
                ...array_keys($target->getAttributes()),
                ...array_keys($target->getRelations()),
                ...array_keys($meta->relations),
                ...array_keys($meta->hasSetter),
                ...array_keys($meta->columns),
            ];
        }

        return array_values(array_unique(
            array_filter($propNames, fn ($name) => is_string($name) && (bool) $name),
        ));
    }

    /**
     * Build a setter for a relation property, which expects either null or an instance of Model.
     * For BelongsTo and MorphTo relations, we can delegate to the associate() method provided by Eloquent.
     * For other relation types, we would need to implement custom logic to handle setting the relation,
     * which is currently not supported and will throw an exception if attempted to be set.
     *
     * @return \Closure(Model ,mixed ):void
     */
    public function buildRelationSetter(RelationMeta $relationMeta): \Closure
    {
        return static function (Model $model, mixed $value) use ($relationMeta): void {
            if (null !== $value && !$value instanceof Model) {
                throw new InvalidRelationValueException($model::class, $relationMeta->name, $value);
            }

            // BelongsTo and its subclass MorphTo both expose the associate() method for setting the relation
            /** @var BelongsTo|MorphTo $relation */
            $relation = $model->{$relationMeta->name}();
            $relation->associate($value);
        };
    }
}
