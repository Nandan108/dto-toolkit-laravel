<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default HTTP error handling
    |--------------------------------------------------------------------------
    |
    | How DTOT processing errors should be exposed in HTTP contexts.
    |
    | Supported modes:
    | - validation_exception : throw Laravel ValidationException (422)
    | - json_response        : return JsonResponse directly
    | - throw                : rethrow DTOT exceptions
    |
    */

    'error_handling' => 'validation_exception',

    /*
    |--------------------------------------------------------------------------
    | Default locale fallback
    |--------------------------------------------------------------------------
    */

    'locale' => 'en',

];
