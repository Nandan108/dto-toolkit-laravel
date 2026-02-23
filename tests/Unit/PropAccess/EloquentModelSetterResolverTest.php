<?php

declare(strict_types=1);

namespace Tests\Unit\PropAccess;

use Nandan108\DtoToolkit\Laravel\Exception\InvalidRelationValueException;
use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelResolverCache;
use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelSetterResolver;
use Nandan108\DtoToolkit\Laravel\PropAccess\WriteUnknownAttrPolicy;
use Nandan108\PropAccess\Exception\AccessorException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\FakeAuthor;
use Tests\Fixtures\FakePost;

require_once __DIR__.'/../../Fixtures/PropAccessModels.php';

final class EloquentModelSetterResolverTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private EloquentModelSetterResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        EloquentModelResolverCache::clear();
        EloquentModelSetterResolver::$writePolicy = WriteUnknownAttrPolicy::ALLOW_UNKNOWN;
        $this->resolver = new EloquentModelSetterResolver();
    }

    public function testItSupportsEloquentModels(): void
    {
        self::assertTrue($this->resolver->supports(new FakePost()));
        self::assertFalse($this->resolver->supports(new \stdClass()));
    }

    public function testAllowUnknownWritesUnknownAttribute(): void
    {
        $post = new FakePost();
        $map = $this->resolver->getSetterMap($post, ['newField']);
        $map['newField']($post, 'x');

        /** @psalm-suppress UndefinedMagicPropertyFetch */
        self::assertSame('x', $post->newField);
    }

    public function testDeclaredOnlyIgnoresUnknownWhenIgnoreFlagTrue(): void
    {
        EloquentModelSetterResolver::$writePolicy = WriteUnknownAttrPolicy::DECLARED_ONLY;

        $post = new FakePost();
        $map = $this->resolver->getSetterMap($post, ['newField'], true);

        self::assertArrayNotHasKey('newField', $map);
    }

    public function testDeclaredOnlyThrowsForUnknownWhenIgnoreFlagFalse(): void
    {
        EloquentModelSetterResolver::$writePolicy = WriteUnknownAttrPolicy::DECLARED_ONLY;

        $this->expectException(AccessorException::class);
        $this->expectExceptionMessage('No writable Eloquent property found');
        $this->resolver->getSetterMap(new FakePost(), ['newField'], false);
    }

    public function testSchemaVerifiedUsesMetaColumnsForUnknownFields(): void
    {
        EloquentModelSetterResolver::$writePolicy = WriteUnknownAttrPolicy::SCHEMA_VERIFIED;

        $post = new FakePost();
        $meta = EloquentModelResolverCache::for($post);
        $meta->columns = ['title' => true];

        $map = $this->resolver->getSetterMap($post, ['title', 'ghost'], true);

        self::assertArrayHasKey('title', $map);
        self::assertArrayNotHasKey('ghost', $map);
    }

    public function testRelationSetterAssociatesBelongsToRelation(): void
    {
        $author = new FakeAuthor();
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $author->id = 12;

        $post = new FakePost();
        $map = $this->resolver->getSetterMap($post, ['author']);
        $map['author']($post, $author);

        self::assertSame($author, $post->getRelation('author'));
        /** @psalm-suppress UndefinedMagicPropertyFetch */
        self::assertSame(12, $post->author_id);
    }

    public function testRelationSetterRejectsInvalidValueType(): void
    {
        $post = new FakePost();
        $map = $this->resolver->getSetterMap($post, ['author']);

        $this->expectException(InvalidRelationValueException::class);
        $map['author']($post, 'invalid');
    }

    public function testUnsupportedRelationSetterThrowsWhenIgnoreFlagFalse(): void
    {
        $this->expectException(AccessorException::class);
        $this->resolver->getSetterMap(new FakePost(), ['comments'], false);
    }

    public function testItBuildsSetterMapFromStringPropName(): void
    {
        $post = new FakePost();
        $map = $this->resolver->getSetterMap($post, 'title');

        self::assertArrayHasKey('title', $map);
        $map['title']($post, 'Updated');
        self::assertSame('Updated', $post->title);
    }

    public function testNullPropNamesInferKnownModelProperties(): void
    {
        $post = new FakePost();
        $map = $this->resolver->getSetterMap($post, null);

        self::assertArrayHasKey('title', $map);
        self::assertArrayHasKey('author', $map);
        self::assertArrayNotHasKey('comments', $map);
    }
}
