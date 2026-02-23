<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel;

/**
 * Base DTO for Laravel applications.
 *
 * Laravel-specific hydration: load from Request.
 * Outbound HTTP response semantics are intentionally left as a separate concern.
 * Note: Exporting to Eloquent models is done via core trait method:
 *       Nandan108\DtoToolkit\Traits\exportToEntity::exportToEntity().
 *       Here, we only need to plug in Eloquent model support for nandan108\PropAccess.
 */
/** @psalm-suppress UnusedClass */
abstract class LaravelDto extends FullDto
{
    // use Traits/LoadsLaravelRequest;
    // use Traits/ReturnsLaravelResponse;
}
