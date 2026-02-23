<?php

declare(strict_types=1);

namespace Tests\Unit\PropAccess;

use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelGetterResolver;
use Nandan108\DtoToolkit\Laravel\PropAccess\EloquentModelResolverCache;
use Nandan108\DtoToolkit\Laravel\PropAccess\RelationAccessPolicy;
use Nandan108\PropAccess\Exception\AccessorException;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\FakeAuthor;
use Tests\Fixtures\FakePost;

require_once __DIR__.'/../../Fixtures/PropAccessModels.php';

final class EloquentModelGetterResolverTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private EloquentModelGetterResolver $resolver;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        EloquentModelResolverCache::clear();
        EloquentModelGetterResolver::setRelationAccessHandler(RelationAccessPolicy::ALLOW_NONE);
        $this->resolver = new EloquentModelGetterResolver();
    }

    public function testItSupportsEloquentModels(): void
    {
        self::assertTrue($this->resolver->supports(new FakePost()));
        self::assertFalse($this->resolver->supports(new \stdClass()));
    }

    public function testItBuildsAttributeGetter(): void
    {
        $post = new FakePost();
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $post->title = 'Hello';

        $map = $this->resolver->getGetterMap($post, ['title']);

        self::assertArrayHasKey('title', $map);
        self::assertSame('Hello', $map['title']($post));
    }

    public function testAllowNoneDeniesRelationAccess(): void
    {
        $post = new FakePost();
        $map = $this->resolver->getGetterMap($post, ['author']);

        $this->expectException(AccessorException::class);
        $this->expectExceptionMessage('Relation access is disabled');
        $map['author']($post);
    }

    public function testAllowLoadedRequiresRelationToBeLoaded(): void
    {
        EloquentModelGetterResolver::setRelationAccessHandler(RelationAccessPolicy::ALLOW_LOADED);

        $post = new FakePost();
        $map = $this->resolver->getGetterMap($post, ['author']);

        $this->expectException(AccessorException::class);
        $this->expectExceptionMessage('is not loaded');
        $map['author']($post);
    }

    public function testAllowLoadedReturnsLoadedRelationWithoutLazyLoading(): void
    {
        EloquentModelGetterResolver::setRelationAccessHandler(RelationAccessPolicy::ALLOW_LOADED);

        $author = new FakeAuthor();
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $author->id = 42;

        $post = new FakePost();
        $post->setRelation('author', $author);

        $map = $this->resolver->getGetterMap($post, ['author']);

        self::assertSame($author, $map['author']($post));
    }

    public function testCustomRelationHandlerIsUsed(): void
    {
        EloquentModelGetterResolver::setRelationAccessHandler(
            static fn (FakePost $model, string $name): string => $model::class.':'.$name,
        );

        $post = new FakePost();
        $map = $this->resolver->getGetterMap($post, ['author']);

        self::assertSame(FakePost::class.':author', $map['author']($post));
    }

    public function testGetRelationAccessHandlerReturnsCurrentHandler(): void
    {
        EloquentModelGetterResolver::setRelationAccessHandler(RelationAccessPolicy::ALLOW_LOADED);

        self::assertSame(
            RelationAccessPolicy::ALLOW_LOADED,
            EloquentModelGetterResolver::getRelationAccessHandler(),
        );
    }

    public function testGetGetterMapReturnsEmptyForNonModelValues(): void
    {
        self::assertSame([], $this->resolver->getGetterMap(new \stdClass(), ['x']));
    }

    public function testItAcceptsASingleStringPropertyName(): void
    {
        $post = new FakePost();
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $post->title = 'Single';

        $map = $this->resolver->getGetterMap($post, 'title');

        self::assertArrayHasKey('title', $map);
        self::assertSame('Single', $map['title']($post));
    }

    public function testAllowLazyResolvesRelationViaNativeLazyLoading(): void
    {
        EloquentModelGetterResolver::setRelationAccessHandler(RelationAccessPolicy::ALLOW_LAZY);

        $author = new FakeAuthor();
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $author->name = 'Ada';
        $author->save();

        $post = new FakePost();
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $post->author_id = $author->id;
        $post->save();

        self::assertFalse($post->relationLoaded('author'));

        $map = $this->resolver->getGetterMap($post, ['author']);
        $resolved = $map['author']($post);

        self::assertInstanceOf(FakeAuthor::class, $resolved);
        self::assertTrue($post->relationLoaded('author'));
        self::assertSame($author->id, $resolved->id);
    }

    public function testNullPropNamesInfersAttributesRelationsAndMetaEntries(): void
    {
        EloquentModelGetterResolver::setRelationAccessHandler(RelationAccessPolicy::ALLOW_LOADED);

        $author = new FakeAuthor();
        $post = new FakePost();
        $post->setRelation('author', $author);
        /** @psalm-suppress UndefinedMagicPropertyAssignment */
        $post->title = 'hello';

        $map = $this->resolver->getGetterMap($post, null);

        self::assertArrayHasKey('title', $map);
        self::assertArrayHasKey('author', $map);
        self::assertArrayHasKey('titleCase', $map);
    }
}
