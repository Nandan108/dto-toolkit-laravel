<?php

declare(strict_types=1);

namespace Tests\Unit\PropAccess;

use Illuminate\Database\Eloquent\Model;
use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelResolverCache;
use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelSetterResolver;
use Nandan108\DtoToolkit\Laravel\PropAccess\WriteUnknownAttrPolicy;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\FakePost;

require_once __DIR__.'/../../Fixtures/PropAccessModels.php';

final class EloquentModelResolverCacheTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        EloquentModelResolverCache::clear();
        EloquentModelSetterResolver::$writePolicy = WriteUnknownAttrPolicy::ALLOW_UNKNOWN;
    }

    public function testForCachesMetadataPerModelClass(): void
    {
        $metaA = EloquentModelResolverCache::for(new FakePost());
        $metaB = EloquentModelResolverCache::for(new FakePost());

        self::assertSame($metaA, $metaB);
    }

    public function testClearResetsCache(): void
    {
        $metaA = EloquentModelResolverCache::for(new FakePost());
        EloquentModelResolverCache::clear();
        $metaB = EloquentModelResolverCache::for(new FakePost());

        self::assertNotSame($metaA, $metaB);
    }

    public function testItCollectsRelationsAndAttributeAccessors(): void
    {
        $meta = EloquentModelResolverCache::for(new FakePost());

        self::assertTrue($meta->isRelation('author'));
        self::assertTrue($meta->isRelation('comments'));
        self::assertTrue($meta->hasSetter('author'));
        self::assertFalse($meta->hasSetter('comments'));
        self::assertTrue($meta->hasGetter('titleCase'));
        self::assertTrue($meta->hasSetter('titleCase'));
    }

    public function testSchemaVerifiedPolicyCollectsAndReusesSchemaColumns(): void
    {
        EloquentModelSetterResolver::$writePolicy = WriteUnknownAttrPolicy::SCHEMA_VERIFIED;

        $metaA = EloquentModelResolverCache::for(new FakePost());
        self::assertTrue($metaA->isColumn('title'));

        $metaB = EloquentModelResolverCache::for(new FakePost());
        self::assertSame($metaA, $metaB);
        self::assertTrue($metaB->isColumn('author_id'));
    }

    public function testItDetectsLegacyAccessorAndMutatorMethodNames(): void
    {
        $meta = EloquentModelResolverCache::for(new LegacyAccessorPost());

        self::assertTrue($meta->hasGetter('legacyTitle'));
        self::assertTrue($meta->hasSetter('legacyTitle'));
    }
}

/** @psalm-suppress PropertyNotSetInConstructor */
final class LegacyAccessorPost extends Model
{
    protected $connection = 'testing';
    protected $table = 'posts';
    public $timestamps = false;
    protected $guarded = [];

    public function getLegacyTitleAttribute(): string
    {
        return 'legacy';
    }

    public function setLegacyTitleAttribute(): string
    {
        return 'legacy';
    }
}
