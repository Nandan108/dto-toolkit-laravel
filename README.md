# DTO Toolkit Laravel Adapter

Laravel adapter for [`nandan108/dto-toolkit`](https://packagist.org/packages/nandan108/dto-toolkit).

This package integrates DTO Toolkit into the Laravel ecosystem. It allows DTOs to:

* be injected directly into controller methods (like `FormRequest`),
* hydrate automatically from the current `Request`,
* throw native Laravel `ValidationException`,
* return JSON responses,
* and read/write Eloquent models via `prop-access`.

The core DTO engine remains framework-agnostic. This package provides Laravel-specific integration only.

---

## Requirements

* PHP `^8.1`
* Laravel `^10.0 || ^11.0 || ^12.0`
* `nandan108/dto-toolkit` `^1.4.4`

---

## Installation

```bash
composer require nandan108/dto-toolkit-laravel
```

The service provider is auto-discovered.

Publish configuration:

```bash
php artisan vendor:publish --tag=dto-toolkit-config
```

---

# Core DTO Base Classes

## `FullDto`

`Nandan108\DtoToolkit\Laravel\FullDto`

Extends DTOT’s full DTO behavior and adds:

* `loadRequest()` / `newFromRequest()`
* `exportToJson()`
* `exportToJsonResponse()`

Use this when you want manual request hydration.

---

## `RequestDto`

`Nandan108\DtoToolkit\Laravel\RequestDto`

Extends `FullDto`.

When injected into a controller method, it:

* automatically hydrates from the current `Request`,
* runs the DTO processing pipeline,
* throws a native Laravel `ValidationException` if validation fails.

Use this class when replacing `FormRequest`.

---

# Controller Example

```php
use App\Dto\StorePostDto;
use App\Models\Post;

public function store(StorePostDto $dto)
{
    $post = $dto->exportToEntity(Post::class);
    $post->save();

    return $dto->exportToJsonResponse(status: 201);
}
```

If DTO validation fails:

* Web requests redirect back with session errors.
* JSON requests return HTTP 422 with Laravel’s standard validation error format.

No manual validation code is required.

---

# Request Hydration

DTOs can hydrate from Laravel request “buckets”:

* `route`
* `query`
* `body`
* `json`
* `files`
* `cookies`

The authenticated user is also available via the `user` bucket.

Merge semantics use PHP array union (`+`):
**the first included bucket wins for duplicate top-level keys.**

Default bucket order is configurable:

```php
// config/dto-toolkit.php
'request_load_include' => ['route', 'query', 'body', 'json', 'files', 'cookies'],
```

Example:

```php
final class SearchDto extends FullDto
{
    public ?string $q = null;
    public ?int $page = 1;
}

$dto = SearchDto::newFromRequest($request, ['query']);
```

---

# Validation Bridge

`Nandan108\DtoToolkit\Laravel\DtoValidationBridge` converts DTOT processing errors into Laravel validation errors:

* Property paths normalized to Laravel dot notation
* Processing trace markers removed
* DTO-level errors mapped to `dto`

`RequestDto` uses this bridge automatically.

As a result, DTOT validators and casters replace traditional Laravel rule arrays, while preserving native Laravel validation behavior.

---

# JSON Export Helpers

`FullDto` provides:

* `exportToJson()`
* `exportToJsonResponse()`

Both support:

* optional wrapping key,
* outbound group filtering,
* custom status codes,
* custom headers,
* JSON encoding options.

Example:

```php
return $dto->exportToJsonResponse(
    status: 201,
    headers: ['X-Request-Id' => $requestId],
    wrapKey: 'data',
    groups: ['public'],
);
```

These helpers are optional conveniences and do not affect validation behavior.

---

# Eloquent PropAccess Adapter

The package registers Eloquent resolvers for `nandan108/prop-access`:

* `EloquentModelGetterResolver`
* `EloquentModelSetterResolver`

Supported capabilities:

* Dynamic attribute access
* Relation-aware getter and setter handling
* BelongsTo / MorphTo association support
* Accessor/mutator and `Attribute::make` detection
* Cached metadata resolution

> Note: Models are never persisted automatically. Saving remains explicit.

---

## Runtime Policies

Configure behavior in `config/dto-toolkit.php`:

```php
'write_unknown_attr_policy' => 'allow_unknown',
'relation_access' => 'allow_loaded',
```

### `write_unknown_attr_policy`

* `allow_unknown` — allow dynamic attributes
* `declared_only` — restrict to declared attributes
* `schema_verified` — validate attribute names against database columns
  *(recommended for local/testing environments)*

### `relation_access`

* `allow_none`
* `allow_loaded`
* `allow_lazy`

---

# Configuration File

Published at:

```
config/dto-toolkit.php
```

Use this file to configure:

* Request bucket order
* Eloquent policies
* Validation behavior

---

# Design Philosophy

This adapter:

* preserves DTOT’s framework-agnostic core,
* integrates cleanly with Laravel’s container lifecycle,
* does not replace Laravel’s HTTP layer,
* and does not persist models automatically.

It aims to make DTO Toolkit feel native inside Laravel without sacrificing structure or determinism.
