<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class FakeAuthor extends Model
{
    protected $connection = 'testing';
    protected $table = 'authors';
    public $timestamps = false;
    protected $guarded = [];
}

final class FakeComment extends Model
{
    protected $connection = 'testing';
    protected $table = 'comments';
    public $timestamps = false;
    protected $guarded = [];
}

final class FakePost extends Model
{
    protected $connection = 'testing';
    protected $table = 'posts';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = [
        'title' => 'initial',
    ];

    /** @psalm-suppress PossiblyUnusedMethod */
    public function author(): BelongsTo
    {
        return $this->belongsTo(FakeAuthor::class);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function comments(): HasMany
    {
        return $this->hasMany(FakeComment::class);
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function titleCase(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): string => strtoupper((string) $value),
            set: static fn (mixed $value): string => strtolower((string) $value),
        );
    }
}
