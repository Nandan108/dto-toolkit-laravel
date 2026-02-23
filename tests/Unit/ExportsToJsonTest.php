<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\ResponseFactory;
use Illuminate\Support\Facades\Facade;
use Nandan108\DtoToolkit\Attribute\Outbound;
use Nandan108\DtoToolkit\Attribute\PropGroups;
use Nandan108\DtoToolkit\Laravel\FullDto;
use PHPUnit\Framework\TestCase;

final class ExportsToJsonTest extends TestCase
{
    private ?Application $app = null;
    private ?Container $previousContainer = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->bootResponseHelperApplication();
    }

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->app) {
            $this->app->flush();
        }

        Facade::setFacadeApplication($this->previousContainer);
        Container::setInstance($this->previousContainer ?? new Container());
        parent::tearDown();
    }

    public function testExportToJsonReturnsJsonString(): void
    {
        $dto = ExportsToJsonTestDto::newFromArray([
            'id'     => 10,
            'name'   => 'Ada',
            'secret' => 'token',
            'url'    => 'https://example.test/path',
        ]);

        $json = $dto->exportToJson(JSON_UNESCAPED_SLASHES, wrapKey: 'data');
        /** @var array{data: array<string, mixed>} $decoded */
        $decoded = json_decode($json, true);

        self::assertSame([
            'data' => [
                'id'  => 10,
                'url' => 'https://example.test/path',
            ],
        ], $decoded);
        self::assertStringContainsString('https://example.test/path', $json);
    }

    public function testExportToJsonWithoutWrapKeyReturnsRootPayload(): void
    {
        $dto = ExportsToJsonTestDto::newFromArray([
            'id'     => 3,
            'name'   => 'NoWrap',
            'secret' => 'x',
            'url'    => 'https://example.test/root',
        ]);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($dto->exportToJson(JSON_UNESCAPED_SLASHES), true);

        self::assertSame([
            'id'  => 3,
            'url' => 'https://example.test/root',
        ], $decoded);
    }

    public function testExportToJsonResponseHonorsWrapKeyGroupsStatusHeadersAndOptions(): void
    {
        $dto = ExportsToJsonTestDto::newFromArray([
            'id'     => 12,
            'name'   => 'Grace',
            'secret' => 'hidden',
            'url'    => 'https://example.test/secret',
        ]);

        $response = $dto->exportToJsonResponse(
            status: 201,
            headers: ['X-Test' => 'ok'],
            options: JSON_UNESCAPED_SLASHES,
            wrapKey: 'payload',
            groups: ['public'],
        );

        /** @var array{payload: array<string, mixed>} $decoded */
        $decoded = json_decode($response->getContent() ?: '', true);

        self::assertSame(201, $response->getStatusCode());
        self::assertSame('ok', $response->headers->get('X-Test'));
        self::assertSame([
            'payload' => [
                'id'   => 12,
                'name' => 'Grace',
                'url'  => 'https://example.test/secret',
            ],
        ], $decoded);
        self::assertStringContainsString('https://example.test/secret', $response->getContent() ?: '');
        self::assertArrayNotHasKey('secret', $decoded['payload']);
    }

    public function testExportToJsonThrowsWhenPayloadIsNotJsonEncodable(): void
    {
        $dto = ExportsToJsonInvalidPayloadTestDto::newFromArray([
            'bad' => NAN,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to export DTO as JSON:');
        $dto->exportToJson();
    }

    private function bootResponseHelperApplication(): void
    {
        $basePath = sys_get_temp_dir().'/dto-toolkit-laravel-json-'.bin2hex(random_bytes(4));
        @mkdir($basePath, 0777, true);

        $app = new Application($basePath);
        $app->instance('config', new Repository([]));

        $viewFactory = $this->createMock(ViewFactoryContract::class);
        $redirector = $this->getMockBuilder(Redirector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $responseFactory = new ResponseFactory($viewFactory, $redirector);
        $app->instance(ResponseFactoryContract::class, $responseFactory);

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
        $this->app = $app;
    }
}

final class ExportsToJsonTestDto extends FullDto
{
    public int $id = 0;

    #[Outbound]
    #[PropGroups('public')]
    public string $name = '';

    #[Outbound]
    #[PropGroups('private')]
    public string $secret = '';

    public string $url = '';
}

final class ExportsToJsonInvalidPayloadTestDto extends FullDto
{
    public float $bad = 0.0;
}
