<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\Traits;

use Illuminate\Http\Request;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Traits\CreatesFromArrayOrEntity;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @method static static newFromRequest(Request $request, ?array $include = null, bool $ignoreUnknownProps = true, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null, bool $clear = true)
 */
trait LoadsArraysEntitiesAndRequests
{
    use CreatesFromArrayOrEntity;

    /**
     * @var ?non-empty-string key by which #[MapFrom] may access the individual request buckets during DTO load phase.
     *                        E.g. #[MapFrom('_req.query.some_param')]
     *                        If null, the buckets will not be included in the merged data and will not be accessible via MapFrom.
     *                        If set, the buckets will be included, as well as the 'user' key for the authenticated user (if any).
     */
    public static ?string $bucketsKey = '_req';

    /**
     * @var list<string> default buckets to merge when preparing input to load in to DTO.
     *                   Both content and order matter, determining which buckets will be included in the loaded input,
     *                   and the precedence of values for duplicate keys (first bucket wins).
     *                   Supported buckets: route, query, body, json, files, cookies.
     *                   Overridable: - per DTO class by re-declaring this static property,
     *                   and per call to loadRequest() by passing $include argument.
     */
    protected static array $defaultLoadRequestInclude = ['route', 'query', 'body', 'json', 'files', 'cookies'];

    /**
     * Supports static `newFromRequest()` through BaseDto::__callStatic().
     *
     * Bucket names: route, query, body, json, files, cookies.
     * Merge semantics use PHP array union (`+`): first included bucket wins per top-level key.
     *
     * @param bool                     $ignoreUnknownProps If false, unknown properties will cause an exception, otherwise they will be ignored
     * @param ProcessingErrorList|null $errorList          Optional error list to collect processing errors (depends on ErrorMode)
     * @param ErrorMode|null           $errorMode          Optional policy that determines whether to fail fast or collect, and
     *                                                     if collecting, which values to set for properties that fail processing (null, input value, or omit entirely)
     * @param bool                     $clear              If true, unfilled properties will be reset to their default values
     */
    public function loadRequest(
        Request $request,
        ?array $include = null,
        bool $ignoreUnknownProps = true,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $clear = true,
    ): static {
        $merged = $buckets = [];

        foreach (static::normalizeInclude($include ?? static::$defaultLoadRequestInclude) as $bucket) {
            /** @var InputBag $json */
            $json = $request->json();

            /** @var array<string, mixed> */
            $bucketData = match ($bucket) {
                'route'   => $this->routeParamsFromRequest($request),
                'query'   => $request->query->all(),
                'body'    => $request->request->all(),
                'json'    => $json->all(),
                'files'   => $request->files->all(),
                'cookies' => $request->cookies->all(),
            };
            $merged += $bucketData;
            null !== static::$bucketsKey && $buckets[$bucket] = $bucketData;
        }

        if (null !== static::$bucketsKey) {
            /** @psalm-var mixed */
            $buckets['user'] = $request->user();
            $merged[static::$bucketsKey] = $buckets;
        }

        return $this->loadArray($merged, $ignoreUnknownProps, $errorList, $errorMode, $clear);
    }

    /** @param list<string> $include */
    public static function setDefaultLoadRequestInclude(array $include): void
    {
        static::$defaultLoadRequestInclude = static::normalizeInclude($include);
    }

    /** @return list<string> */
    public static function getDefaultLoadRequestInclude(): array
    {
        return static::$defaultLoadRequestInclude;
    }

    /**
     * @param array<array-key, mixed> $include
     *
     * @return list<'route'|'query'|'body'|'json'|'files'|'cookies'>
     */
    protected static function normalizeInclude(array $include): array
    {
        $allowedBuckets = ['route', 'query', 'body', 'json', 'files', 'cookies'];
        $normalized = [];

        foreach ($include as $bucket) {
            if (!is_string($bucket)) {
                throw new \InvalidArgumentException('loadRequest include values must be strings.');
            }

            if (!in_array($bucket, $allowedBuckets, true)) {
                throw new \InvalidArgumentException("Unsupported loadRequest include bucket '{$bucket}'.");
            }

            if (!in_array($bucket, $normalized, true)) {
                $normalized[] = $bucket;
            }
        }

        return $normalized;
    }

    protected function routeParamsFromRequest(Request $request): array
    {
        $route = $request->route();
        if (is_object($route) && method_exists($route, 'parameters')) {
            /** @psalm-var mixed */
            $params = $route->parameters();

            // We trust that route parameters will always be either empty/non-arrays or string-keyed arrays
            /** @var array<string, mixed> */
            return is_array($params) ? $params : [];
        }

        return [];
    }
}
