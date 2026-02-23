<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Nandan108\DtoToolkit\Laravel\DtoToolkitServiceProvider;
use Nandan108\DtoToolkit\Laravel\FullDto;
use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelGetterResolver;
use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelSetterResolver;
use Nandan108\DtoToolkit\Laravel\PropAccess\RelationAccessPolicy;
use Nandan108\DtoToolkit\Laravel\PropAccess\WriteUnknownAttrPolicy;
use PHPUnit\Framework\TestCase;

final class DtoToolkitServiceProviderTest extends TestCase
{
    /** @var list<string> */
    private array $previousDefaultInclude;
    private WriteUnknownAttrPolicy $previousWritePolicy;
    /** @var RelationAccessPolicy|callable */
    private mixed $previousRelationAccessHandler;
    private ?Application $app = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->previousDefaultInclude = FullDto::getDefaultLoadRequestInclude();
        $this->previousWritePolicy = EloquentModelSetterResolver::$writePolicy;
        $this->previousRelationAccessHandler = EloquentModelGetterResolver::getRelationAccessHandler();
    }

    #[\Override]
    protected function tearDown(): void
    {
        FullDto::setDefaultLoadRequestInclude($this->previousDefaultInclude);
        EloquentModelSetterResolver::$writePolicy = $this->previousWritePolicy;
        EloquentModelGetterResolver::setRelationAccessHandler($this->previousRelationAccessHandler);

        if ($this->app) {
            $this->app->flush();
        }

        parent::tearDown();
    }

    public function testRegisterMergesPackageConfiguration(): void
    {
        $app = $this->makeApp();
        $provider = new DtoToolkitServiceProvider($app);

        $provider->register();

        self::assertSame('en', $app['config']->get('dto-toolkit.locale'));
        self::assertSame(
            ['route', 'query', 'body', 'json', 'files', 'cookies'],
            $app['config']->get('dto-toolkit.request_load_include'),
        );
    }

    public function testBootAppliesConfigToStaticRuntimeOptions(): void
    {
        $app = $this->makeApp([
            'app.debug'                             => true,
            'dto-toolkit.request_load_include'      => ['query', 'route'],
            'dto-toolkit.write_unknown_attr_policy' => WriteUnknownAttrPolicy::DECLARED_ONLY->value,
            'dto-toolkit.relation_access'           => RelationAccessPolicy::ALLOW_LAZY->value,
        ]);
        $provider = new DtoToolkitServiceProvider($app);
        $provider->register();

        $provider->boot();

        self::assertSame(['query', 'route'], FullDto::getDefaultLoadRequestInclude());
        self::assertSame(WriteUnknownAttrPolicy::DECLARED_ONLY, EloquentModelSetterResolver::$writePolicy);
        self::assertSame(RelationAccessPolicy::ALLOW_LAZY, EloquentModelGetterResolver::getRelationAccessHandler());
    }

    public function testBootIgnoresNonArrayRequestLoadIncludeConfig(): void
    {
        FullDto::setDefaultLoadRequestInclude(['files']);

        $app = $this->makeApp([
            'dto-toolkit.request_load_include' => 'query',
        ]);
        $provider = new DtoToolkitServiceProvider($app);
        $provider->register();

        $provider->boot();

        self::assertSame(['files'], FullDto::getDefaultLoadRequestInclude());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function makeApp(array $config = []): Application
    {
        $basePath = sys_get_temp_dir().'/dto-toolkit-laravel-test-'.bin2hex(random_bytes(4));
        @mkdir($basePath, 0777, true);
        @mkdir($basePath.'/config', 0777, true);

        $app = new Application($basePath);

        $repo = new Repository([
            'app'         => ['debug' => false],
            'dto-toolkit' => [],
        ]);

        foreach ($config as $key => $value) {
            $repo->set($key, $value);
        }

        $app->instance('config', $repo);

        $this->app = $app;

        return $app;
    }
}
