<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel;

use Illuminate\Http\Request;
use Nandan108\DtoToolkit\Attribute\Inject;
use Nandan108\DtoToolkit\Contracts\Bootable;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;

/**
 * Base class for DTOs that are meant to be hydrated from Laravel
 * HTTP Requests automatically when used as controller action parameters.
 * The request is loaded into the DTO while it is prepared for injection. *.
 */
abstract class RequestDto extends FullDto implements Bootable, Injectable
{
    // Laravel HTTP Request property
    #[Inject]
    protected Request $_request;

    #[\Override]
    public function boot(): void
    {
        // This method is called after the DTO is fully constructed and dependencies are injected.
        // You can perform any additional initialization here if needed.
        $this->loadRequest(
            request: $this->_request,
            errorList: $errors = new ProcessingErrorList(),
            errorMode: ErrorMode::CollectNone,
        );

        // After loading the request, if there are any processing errors,
        // throw a Laravel ValidationException!
        if ($errors->count()) {
            DtoValidationBridge::throwLaravelValidationException($errors);
        }
    }
}
