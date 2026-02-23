<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\PropAccess;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RelationMeta
{
    public string $name;
    public bool $setterSupported;

    public function __construct(
        \ReflectionMethod $method,
        string $relationClass,
    ) {
        $this->name = $method->getName();
        // Note: MorphTo extends BelongsTo
        $this->setterSupported = is_a($relationClass, BelongsTo::class, true);
    }
}
