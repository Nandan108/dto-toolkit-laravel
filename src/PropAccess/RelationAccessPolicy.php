<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\PropAccess;

enum RelationAccessPolicy: string
{
    case ALLOW_NONE = 'allow_none';     // attributes-only
    case ALLOW_LOADED = 'allow_loaded'; // pre-loaded relations only
    case ALLOW_LAZY = 'allow_lazy';     // allow native lazy loading
}
