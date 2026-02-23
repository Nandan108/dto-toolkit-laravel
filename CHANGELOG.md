# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog,
and this project adheres to Semantic Versioning.

## [v0.1.0] - 2026-02-23

### Added
- Initial public release of `nandan108/dto-toolkit-laravel`.
- Added `Nandan108\DtoToolkit\Laravel\FullDto` as a Laravel-focused base DTO with request hydration and JSON export support.
- Added `Nandan108\DtoToolkit\Laravel\RequestDto` for auto-loading from the current `Illuminate\Http\Request` on container resolution and raising `ValidationException` on processing errors.
- Added `Nandan108\DtoToolkit\Laravel\DtoValidationBridge` to map DTOT processing errors into Laravel validation messages and exceptions.
- Added request hydration via `LoadsArraysEntitiesAndRequests` with configurable include buckets: `route`, `query`, `body`, `json`, `files`, and `cookies`.
- Added JSON export helpers via `ExportsToJson`: `exportToJson()` and `exportToJsonResponse()` with wrap key and outbound group support.
- Added Laravel Eloquent PropAccess adapters:
  - `EloquentModelGetterResolver`
  - `EloquentModelSetterResolver`
  - `EloquentModelResolverCache`
  - `EloquentModelMeta`
  - `RelationMeta`
  - `RelationAccessPolicy`
  - `WriteUnknownAttrPolicy`
- Added adapter exception hierarchy:
  - `AdapterException`
  - `InvalidRelationValueException`
- Added package namespace autoload mapping `Nandan108\\DtoToolkit\\Laravel\\`.
- Added service provider boot integration to:
  - initialize container bridge,
  - register default and Laravel-specific PropAccess resolvers,
  - apply write and relation access policies from config,
  - configure default request hydration includes.
- Added `LaravelDto` extension on top of `FullDto`.
- Added package config option `dto-toolkit.request_load_include`.
- Added translation file scaffold under `resources/lang/en/messages.php`.
- Added dependency baseline with `nandan108/dto-toolkit` `^1.4.4`.
- Added Psalm configuration and helper scripts in `composer.json`.
- Added feature and unit test coverage for request DTO injection/validation bridge behavior, service provider boot behavior, request loading, JSON export, relation metadata, resolver behavior, and resolver caching.
- Added test bootstrap with in-memory SQLite schema for Eloquent model fixtures.
