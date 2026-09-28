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

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Transformer\TypeSetterTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(TypeSetterTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(TypeSetterTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(TypeSetterTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(TypeSetterTransformer::class, 'getCode')]
class TypeSetterTransformerTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, string, mixed}>
     */
    public static function typesProvider(): iterable
    {
        yield 'string to int' => ['123', 'int', 123];
        yield 'string to integer' => ['12abc', 'integer', 12];
        yield 'string to float' => ['1.5', 'float', 1.5];
        yield 'string to double' => ['2', 'double', 2.0];
        yield 'int to string' => [123, 'string', '123'];
        yield 'string to bool' => ['0', 'bool', false];
        yield 'int to boolean' => [1, 'boolean', true];
        yield 'scalar to array' => ['foo', 'array', ['foo']];
        yield 'value to null' => ['foo', 'null', null];
    }

    #[DataProvider('typesProvider')]
    public function testTransform(mixed $value, string $type, mixed $expected): void
    {
        $transformer = new TypeSetterTransformer();

        $this->assertSame($expected, $transformer->transform($value, $this->resolveOptions($transformer, $type)));
    }

    public function testTransformToObject(): void
    {
        $transformer = new TypeSetterTransformer();

        $result = $transformer->transform(['foo' => 'bar'], $this->resolveOptions($transformer, 'object'));

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('bar', $result->foo);
    }

    public function testConfigureOptionsRejectsInvalidType(): void
    {
        $transformer = new TypeSetterTransformer();

        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($transformer, 'resource');
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $this->assertSame('type_setter', (new TypeSetterTransformer())->getCode());
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveOptions(TypeSetterTransformer $transformer, string $type): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve(['type' => $type]);
    }
}
