<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel;

use Nandan108\DtoToolkit\Laravel\Traits\ExportsToJson;
use Nandan108\DtoToolkit\Laravel\Traits\LoadsArraysEntitiesAndRequests;

/**
 * Laravel-focused DTO base class with Request hydration and JSON export support.
 */
class FullDto extends \Nandan108\DtoToolkit\Core\FullDto
{
    use ExportsToJson;
    use LoadsArraysEntitiesAndRequests;
}
