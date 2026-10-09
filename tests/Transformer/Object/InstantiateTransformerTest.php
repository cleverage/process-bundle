<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Transformer\Object;

use CleverAge\ProcessBundle\Transformer\Object\InstantiateTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(InstantiateTransformer::class)]
class InstantiateTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('instantiate', (new InstantiateTransformer())->getCode());
    }

    public function testListIsPassedAsPositionalArguments(): void
    {
        $transformer = new InstantiateTransformer();
        $options = $this->resolveOptions($transformer, ['class' => \DateTimeImmutable::class]);

        $result = $transformer->transform(['2024-01-02 03:04:05', new \DateTimeZone('Europe/Paris')], $options);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2024-01-02T03:04:05+01:00', $result->format(\DATE_ATOM));
    }

    public function testStringKeysArePassedAsNamedArguments(): void
    {
        $transformer = new InstantiateTransformer();
        $options = $this->resolveOptions($transformer, ['class' => \DateTimeImmutable::class]);

        $result = $transformer->transform(['timezone' => new \DateTimeZone('UTC'), 'datetime' => '2024-01-02'], $options);

        self::assertInstanceOf(\DateTimeImmutable::class, $result);
        self::assertSame('2024-01-02T00:00:00+00:00', $result->format(\DATE_ATOM));
    }

    public function testEmptyArrayCallsTheConstructorWithoutArguments(): void
    {
        $transformer = new InstantiateTransformer();
        $options = $this->resolveOptions($transformer, ['class' => \ArrayObject::class]);

        $result = $transformer->transform([], $options);

        self::assertInstanceOf(\ArrayObject::class, $result);
        self::assertCount(0, $result);
    }

    public function testEachCallCreatesANewInstance(): void
    {
        $transformer = new InstantiateTransformer();
        $options = $this->resolveOptions($transformer, ['class' => \ArrayObject::class]);

        self::assertNotSame($transformer->transform([], $options), $transformer->transform([], $options));
    }

    public function testNonArrayInputThrows(): void
    {
        $transformer = new InstantiateTransformer();
        $options = $this->resolveOptions($transformer, ['class' => \ArrayObject::class]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Input value must be an array for transformer instantiate');

        $transformer->transform('foo', $options);
    }

    public function testUnknownClassThrows(): void
    {
        $transformer = new InstantiateTransformer();
        $options = $this->resolveOptions($transformer, ['class' => 'App\Unknown\Foo']);

        $this->expectException(\ReflectionException::class);

        $transformer->transform([], $options);
    }

    public function testClassIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions(new InstantiateTransformer(), []);
    }

    public function testClassMustBeAString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions(new InstantiateTransformer(), ['class' => new \ArrayObject()]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(InstantiateTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
