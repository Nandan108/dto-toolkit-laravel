<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Validation\ValidationException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Laravel\DtoValidationBridge;
use PHPUnit\Framework\TestCase;

final class DtoValidationBridgeTest extends TestCase
{
    private ?Container $previousContainer = null;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->bootValidationContainer();
    }

    #[\Override]
    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousContainer);
        Container::setInstance($this->previousContainer ?? new Container());

        parent::tearDown();
    }

    public function testToValidationMessagesMapsAndNormalizesPropertyPaths(): void
    {
        $errors = [
            $this->makeProcessingException('First error', 'items[3]{Mod\PerItem}.price{CastTo\Numeric}'),
            $this->makeProcessingException('Second error', 'items[3].price'),
            $this->makeProcessingException('DTO-level error', null),
        ];

        $messages = DtoValidationBridge::toValidationMessages($errors);

        self::assertSame([
            'items.3.price' => ['First error', 'Second error'],
            'dto'           => ['DTO-level error'],
        ], $messages);

        $messages = DtoValidationBridge::toValidationMessages($errors[0]);
        self::assertSame([
            'items.3.price' => ['First error'],
        ], $messages);
    }

    public function testToValidationMessagesRejectsInvalidArrayPayload(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('array must contain only ProcessingException instances');

        /** @psalm-suppress MixedArgumentTypeCoercion */
        DtoValidationBridge::toValidationMessages([
            'not-an-exception',
        ]);
    }

    public function testThrowLaravelValidationExceptionBuildsFieldBasedErrors(): void
    {
        $errors = [
            $this->makeProcessingException('Invalid amount', 'order.items[1].amount'),
            $this->makeProcessingException('Order is invalid', ''),
        ];

        try {
            DtoValidationBridge::throwLaravelValidationException($errors);
            self::fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            self::assertSame([
                'order.items.1.amount' => ['Invalid amount'],
                'dto'                  => ['Order is invalid'],
            ], $e->errors());
        }
    }

    private function bootValidationContainer(): void
    {
        $container = new Container();
        $translator = new Translator(new ArrayLoader(), 'en');
        $validatorFactory = new ValidationFactory($translator, $container);

        $container->instance('validator', $validatorFactory);
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    /** @psalm-suppress UnusedMethodCall */
    private function makeProcessingException(string $message, ?string $propertyPath): ProcessingException
    {
        $reflection = new \ReflectionClass(ProcessingException::class);
        $exception = $reflection->newInstanceWithoutConstructor();

        $propertyPathProperty = new \ReflectionProperty(ProcessingException::class, 'propertyPath');
        $propertyPathProperty->setAccessible(true);
        $propertyPathProperty->setValue($exception, $propertyPath);

        $messageProperty = new \ReflectionProperty(\Exception::class, 'message');
        $messageProperty->setAccessible(true);
        $messageProperty->setValue($exception, $message);

        return $exception;
    }
}
