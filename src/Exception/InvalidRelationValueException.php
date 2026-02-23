<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\Exception;

final class InvalidRelationValueException extends AdapterException
{
    public function __construct(
        string $modelClass,
        string $relationName,
        mixed $givenValue,
    ) {
        $givenType = get_debug_type($givenValue);
        parent::__construct(
            "Invalid value for relation '{$relationName}' on model '{$modelClass}': "
            .'expected an instance of Illuminate\\Database\\Eloquent\\Model, '
            ."got '{$givenType}'.",
        );
    }
}
