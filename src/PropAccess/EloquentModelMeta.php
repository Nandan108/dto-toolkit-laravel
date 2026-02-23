<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\PropAccess;

use Illuminate\Database\Eloquent\Model;

final class EloquentModelMeta
{
    /** @param class-string<Model> $class */
    public function __construct(string $class)
    {
        $this->ref = new \ReflectionClass($class);
    }

    /** @var \ReflectionClass<Model> */
    public \ReflectionClass $ref;

    /** @var array<string, RelationMeta> */
    public array $relations = [];

    /** @var array<string, true> */
    public array $hasGetter = [];

    /** @var array<string, true> */
    public array $hasSetter = [];

    public function isRelation(string $name): bool
    {
        return isset($this->relations[$name]);
    }

    public function relation(string $name): ?RelationMeta
    {
        return $this->relations[$name] ?? null;
    }

    public function hasGetter(string $name): bool
    {
        return isset($this->hasGetter[$name]);
    }

    public function hasSetter(string $name): bool
    {
        return isset($this->hasSetter[$name]);
    }

    /** @var array<string, true> */
    public array $columns = [];

    public function isColumn(string $name): bool
    {
        return isset($this->columns[$name]);
    }
}
