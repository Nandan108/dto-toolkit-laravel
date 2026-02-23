<?php

declare(strict_types=1);

namespace Tests\Unit\PropAccess;

use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelMeta;
use Nandan108\DtoToolkit\Laravel\PropAccess\RelationAccessPolicy;
use Nandan108\DtoToolkit\Laravel\PropAccess\WriteUnknownAttrPolicy;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\FakePost;

require_once __DIR__.'/../../Fixtures/PropAccessModels.php';

final class EloquentModelMetaAndEnumTest extends TestCase
{
    public function testModelMetaRelationGetterSetterAndColumnHelpers(): void
    {
        $meta = new EloquentModelMeta(FakePost::class);
        $meta->hasGetter['title'] = true;
        $meta->hasSetter['title'] = true;
        $meta->columns['title'] = true;

        self::assertTrue($meta->hasGetter('title'));
        self::assertTrue($meta->hasSetter('title'));
        self::assertTrue($meta->isColumn('title'));
        self::assertFalse($meta->isColumn('unknown'));
        self::assertNull($meta->relation('missing'));
    }

    public function testPolicyEnumsExposeExpectedValues(): void
    {
        self::assertSame('allow_none', RelationAccessPolicy::ALLOW_NONE->value);
        self::assertSame('allow_loaded', RelationAccessPolicy::ALLOW_LOADED->value);
        self::assertSame('allow_lazy', RelationAccessPolicy::ALLOW_LAZY->value);

        self::assertSame('allow_unknown', WriteUnknownAttrPolicy::ALLOW_UNKNOWN->value);
        self::assertSame('declared_only', WriteUnknownAttrPolicy::DECLARED_ONLY->value);
        self::assertSame('schema_verified', WriteUnknownAttrPolicy::SCHEMA_VERIFIED->value);
    }
}
