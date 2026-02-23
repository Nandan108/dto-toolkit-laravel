<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Route;
use Nandan108\DtoToolkit\Laravel\FullDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\InputBag;

final class LoadsArraysEntitiesAndRequestsTest extends TestCase
{
    /** @var list<string> */
    private array $previousDefaultInclude;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->previousDefaultInclude = LoadsArraysEntitiesAndRequestsTestDto::getDefaultLoadRequestInclude();
    }

    #[\Override]
    protected function tearDown(): void
    {
        LoadsArraysEntitiesAndRequestsTestDto::setDefaultLoadRequestInclude($this->previousDefaultInclude);
        parent::tearDown();
    }

    public function testLoadRequestUsesIncludeOrderWithArrayUnionSemantics(): void
    {
        $request = $this->makeRequest();
        $dto = new LoadsArraysEntitiesAndRequestsTestDto();
        $dto = $dto->loadRequest($request, ['body', 'query', 'route', 'json', 'cookies', 'files']);

        self::assertSame('body-id', $dto->id);
        self::assertSame('q-value', $dto->queryOnly);
        self::assertSame('route-value', $dto->routeOnly);
        self::assertSame('json-value', $dto->jsonOnly);
        self::assertSame('cookie-value', $dto->token);
        self::assertInstanceOf(UploadedFile::class, $dto->avatar);
    }

    public function testLoadRequestDefaultsToStaticIncludeListWhenIncludeIsNull(): void
    {
        $request = $this->makeRequest();
        LoadsArraysEntitiesAndRequestsTestDto::setDefaultLoadRequestInclude(['query', 'body']);

        $dto = new LoadsArraysEntitiesAndRequestsTestDto();
        $dto->loadRequest($request, null);

        self::assertSame('query-id', $dto->id);
    }

    public function testStaticNewFromRequestIsRoutedToLoadRequest(): void
    {
        $request = $this->makeRequest();
        $dto = LoadsArraysEntitiesAndRequestsTestDto::newFromRequest($request, ['route']);

        self::assertSame('route-id', $dto->id);
    }

    public function testLoadRequestRejectsInvalidBucketNames(): void
    {
        $request = $this->makeRequest();
        $dto = new LoadsArraysEntitiesAndRequestsTestDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Unsupported loadRequest include bucket 'invalid'.");
        $dto->loadRequest($request, ['invalid']);
    }

    public function testLoadRequestRejectsNonStringBucketValues(): void
    {
        $request = $this->makeRequest();
        $dto = new LoadsArraysEntitiesAndRequestsTestDto();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('loadRequest include values must be strings.');
        $dto->loadRequest($request, ['route', 123]);
    }

    public function testLoadRequestRouteBucketWithoutBoundRouteYieldsEmptyRouteParams(): void
    {
        $request = Request::create('/users?id=q-id', 'GET');
        $dto = new LoadsArraysEntitiesAndRequestsTestDto();
        $dto->loadRequest($request, ['route', 'query']);

        self::assertSame('q-id', $dto->id);
        self::assertSame('', $dto->routeOnly);
    }

    private function makeRequest(): Request
    {
        $path = tempnam(sys_get_temp_dir(), 'dto-toolkit-test-');
        if (false === $path) {
            throw new \RuntimeException('Could not create temp file for uploaded file test fixture.');
        }
        file_put_contents($path, 'avatar-content');
        $uploaded = new UploadedFile($path, 'avatar.txt', 'text/plain', null, true);

        $request = Request::create(
            uri: '/users/route-id?id=query-id&queryOnly=q-value',
            method: 'POST',
            parameters: ['id' => 'body-id'],
            cookies: ['token' => 'cookie-value'],
            files: ['avatar' => $uploaded],
        );
        $request->setJson(new InputBag([
            'id'       => 'json-id',
            'jsonOnly' => 'json-value',
        ]));

        $route = new Route(['POST'], '/users/{id}', static fn () => null);
        $route->bind($request);
        $route->setParameter('routeOnly', 'route-value');
        $request->setRouteResolver(static fn (): Route => $route);

        return $request;
    }
}

final class LoadsArraysEntitiesAndRequestsTestDto extends FullDto
{
    public string $id = '';
    public string $queryOnly = '';
    public string $routeOnly = '';
    public string $jsonOnly = '';
    public string $token = '';
    public ?UploadedFile $avatar = null;
}
