<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel;

use Nandan108\DtoToolkit\Core\FullDto;

/**
 * Base DTO for Laravel applications.
 *
 * Laravel-specific traits: load from Request and export to Responses
 * Note: Exporting to Eloquent models is done via core trait method:
 *       Nandan108\DtoToolkit\Traits\exportToEntity::exportToEntity().
 *       Here, we only need to plug in Eloquent model support for nandan108\PropAccess.
 */
abstract class LaravelDto extends FullDto
{
    // use Traits/LoadsLaravelRequest;
    // use Traits/ReturnsLaravelResponse;
}
