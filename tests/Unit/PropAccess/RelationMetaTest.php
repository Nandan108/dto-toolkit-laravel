<?php

declare(strict_types=1);

namespace Tests\Unit\PropAccess;

use Nandan108\DtoToolkit\Laravel\PropAccess\RelationMeta;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\FakePost;

require_once __DIR__.'/../../Fixtures/PropAccessModels.php';

final class RelationMetaTest extends TestCase
{
    public function testRelationMetaDetectsSupportedAndUnsupportedSetters(): void
    {
        $authorMethod = new \ReflectionMethod(FakePost::class, 'author');
        $commentsMethod = new \ReflectionMethod(FakePost::class, 'comments');
        $imageableMethod = new \ReflectionMethod(FakePost::class, 'imageable');

        $belongsToMeta = new RelationMeta($authorMethod, $this->namedReturnType($authorMethod));
        $hasManyMeta = new RelationMeta($commentsMethod, $this->namedReturnType($commentsMethod));
        $morphToMeta = new RelationMeta($imageableMethod, $this->namedReturnType($imageableMethod));

        self::assertTrue($belongsToMeta->setterSupported);
        self::assertTrue($morphToMeta->setterSupported);
        self::assertFalse($hasManyMeta->setterSupported);
    }

    private function namedReturnType(\ReflectionMethod $method): string
    {
        $returnType = $method->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $returnType);

        return $returnType->getName();
    }
}
