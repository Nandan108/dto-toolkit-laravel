# DTOT Laravel Adapter Backlog

## PBIs
- [01] Adapter package auto-discovery for bootstrapping at framework boot time
- [05] DI container wiring (DtoToolkit\Support\ContainerBridge::setContainer(...))
- [01] Laravel adapter for i18n of error rendering and translations
    - include error templates, render with laravel's own error message rendering system?
    - optional feature (via config switch): auto catch ProcessingExceptions and return corresponding ProblemDetail responses
- [02] `fromRequest()` for DTO hydration from HTTP requests
    - should merge inputs (url + body + cookies + files) in a configurable way, overridable by optional argument (e.g. a list<RequestInputType> )
- [07] `Cast\ToCarbon()` caster
- [03] prop-access adapter for Eloquent Models, with read/write access to attributes
    - fields declared via `fillable`, `guarded`, `casts`
    - fields declared via accessors (old or new syntax), mutators, Attribute::make(),
    - relationships (feature to be defined)
    - no access to dynamic parameters
- [04] `toResponse()` generation
- [06] Graceful handling of validation and casting exceptions in HTTP contexts, with standardized API error responses

So, I'll need to have the adapter package automatically discovered, if possible, and booted at the same time as the rest the framework, which will allow it to hook up DI (register the Laravel container in ContainerBridge), boot prop-access and register a Eloquent Model prop-access adapter (so Models graphs can be navigated like any other object graphs).
I'll need to make a couple traits to hold toResponse() (or toJsonResponse() ?), `->loadRequest()` and declare the corresponding magic method `::newFromRequest()`, and a new FullDto (or LaravelDto? LaravelFullDto?) that includes those traits.
And... I'll need to manage DTOT's ProcessingException and ProcessingErrorList into something native, tho I'm not sure what that entails. I guess providing standard user-land error messages in English is part of it.

