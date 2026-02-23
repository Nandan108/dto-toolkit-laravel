<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\PropAccess;

enum WriteUnknownAttrPolicy: string
{
    /**
     * Allow writing to unknown attributes (default Eloquent behavior)
     * This is the most permissive option, allowing writes to any attribute, including those not
     * declared in $casts, $fillable, or $guarded, and without explicit setter/mutator methods.
     * Lowest overhead, suitable for:
     * - Quick prototyping / maximum flexibility
     * - Confidence is earned through a solid test suite and CI => OK for production use.
     */
    case ALLOW_UNKNOWN = 'allow_unknown';

    /**
     * Restrict writable attribute to those declared in $casts, $fillable or $guarded (strict mode),
     * on top of those with explicit setter/mutator methods. This is a "best effort" protection
     * against typos and unintended writes, but without the overhead of DB schema introspection.
     * Suitable for production use.
     */
    case DECLARED_ONLY = 'declared_only';

    /**
     * Restrict writable attributes to actual DB columns, by loading the table schema from the database.
     * This provides the strongest protection against typos and unintended writes, but with the overhead
     * of a DB query to load the schema. Suitable for development mode.
     */
    case SCHEMA_VERIFIED = 'schema_verified'; // dev mode default - DO NOT USE IN PRODUCTION
}
