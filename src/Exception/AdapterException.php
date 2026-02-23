<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\Exception;

use Nandan108\DtoToolkit\Contracts\DtoToolkitException;

abstract class AdapterException extends \RuntimeException implements DtoToolkitException
{
}
