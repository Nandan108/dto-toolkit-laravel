<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Laravel\DtoToolkitServiceProvider;
use Nandan108\DtoToolkit\Laravel\RequestDto;
use PHPUnit\Framework\TestCase;

final class RequestDtoFeatureTest extends TestCase
{
    private ?Application $app = null;
    private ?Container $previousContainer = null;
    private Router $router;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->bootApplication();
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

    public function testRequestDtoIsInjectedAndValidatedForValidInput(): void
    {
        $response = $this->dispatchPost([
            'name'    => 'Ada',
            'numbers' => '2;5.5;7;5',
        ]);

        self::assertSame(200, $response->getStatusCode());
        /** @var array{name: string, numbers: string} $payload */
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Ada', $payload['name']);
        self::assertSame('2.00,5.50,7.00,5.00', $payload['numbers']);
    }

    public function testRequestDtoThrowsValidationExceptionForBlankName(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('name');

        try {
            $this->dispatchPost([
                'name'    => ' ',
                'numbers' => '2;5.5;7',
            ]);
        } catch (ValidationException $e) {
            self::assertArrayHasKey('name', $e->errors());
            throw $e;
        }
    }

    public function testRequestDtoThrowsValidationExceptionForNonNumericItem(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->dispatchPost([
                'name'    => 'Ada',
                'numbers' => '2;foo;5',
            ]);
        } catch (ValidationException $e) {
            self::assertArrayHasKey('numbers.1', $e->errors());
            throw $e;
        }
    }

    public function testRequestDtoThrowsValidationExceptionForOutOfRangeItem(): void
    {
        $this->expectException(ValidationException::class);

        try {
            $this->dispatchPost([
                'name'    => 'Ada',
                'numbers' => '2;5.5; 11; 5',
            ]);
        } catch (ValidationException $e) {
            self::assertArrayHasKey('numbers.2', $e->errors());
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function dispatchPost(array $payload): JsonResponse
    {
        $request = Request::create('/content', 'POST', $payload);
        $this->app?->instance('request', $request);
        $this->app?->instance(Request::class, $request);

        /** @var JsonResponse $response */
        $response = $this->router->dispatch($request);

        return $response;
    }

    private function bootApplication(): void
    {
        $basePath = sys_get_temp_dir().'/dto-toolkit-laravel-request-dto-'.bin2hex(random_bytes(4));
        @mkdir($basePath, 0777, true);
        @mkdir($basePath.'/config', 0777, true);

        $app = new Application($basePath);
        $app->instance('config', new Repository([
            'app'         => ['debug' => false],
            'dto-toolkit' => [],
        ]));

        $translator = new Translator(new ArrayLoader(), 'en');
        $app->instance('translator', $translator);
        $app->instance('validator', new ValidationFactory($translator, $app));

        Container::setInstance($app);
        Facade::setFacadeApplication($app);

        $provider = new DtoToolkitServiceProvider($app);
        $provider->register();
        $provider->boot();

        $router = new Router(new Dispatcher($app), $app);
        $router->post('/content', TestRequestDtoController::class);

        $this->app = $app;
        $this->router = $router;
    }
}

final class TestContentDto extends RequestDto
{
    #[Assert\IsBlank(false)]
    public ?string $name = null;

    #[CastTo\Split(';')]
    #[Mod\PerItem(3)]
    #[CastTo\Floating]
    #[Assert\Range(1, 10)]
    #[CastTo\NumericString(2)]
    #[CastTo\Join(',')]
    public ?string $numbers = null;
}

final class TestRequestDtoController
{
    public function __invoke(TestContentDto $dto): JsonResponse
    {
        return $dto->exportToJsonResponse();
    }
}
