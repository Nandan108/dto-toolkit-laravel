<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

$capsule = new Capsule();
$capsule->addConnection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'prefix'   => '',
], 'testing');
$capsule->getDatabaseManager()->setDefaultConnection('testing');
$capsule->setAsGlobal();
$capsule->bootEloquent();

$schema = $capsule->schema();

$schema->create('authors', static function (Blueprint $table): void {
    $table->id();
    $table->string('name')->nullable();
});

$schema->create('comments', static function (Blueprint $table): void {
    $table->id();
    $table->unsignedBigInteger('post_id')->nullable();
    $table->text('body')->nullable();
});

$schema->create('posts', static function (Blueprint $table): void {
    $table->id();
    $table->string('title')->nullable();
    $table->unsignedBigInteger('author_id')->nullable();
    $table->unsignedBigInteger('imageable_id')->nullable();
    $table->string('imageable_type')->nullable();
});
