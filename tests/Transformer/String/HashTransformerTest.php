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

namespace CleverAge\ProcessBundle\Tests\Transformer\String;

use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Transformer\String\HashTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[CoversClass(HashTransformer::class)]
class HashTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new HashTransformer();

        $this->assertSame('hash', $transformer->getCode());
        $this->assertSame(md5('value'), $transformer->transform('value', $this->resolve(['algo' => 'md5'])));
        $this->assertSame(md5('12'), $transformer->transform(12, $this->resolve(['algo' => 'md5'])));
        $this->assertSame(md5(''), $transformer->transform(null, $this->resolve(['algo' => 'md5'])));
        $this->assertSame(hash('sha256', 'value', true), $transformer->transform('value', $this->resolve(['algo' => 'sha256', 'raw_output' => true])));
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'value';
            }
        };
        $this->assertSame(md5('value'), $transformer->transform($stringable, $this->resolve(['algo' => 'md5'])));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'array' => [['a']];
        yield 'object' => [new \stdClass()];
    }

    /**
     * An array was hashed as the string "Array": the same hash for every array.
     */
    #[DataProvider('provideInvalidValues')]
    public function testInvalidValue(mixed $value): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('The hash transformer expects a string, '.get_debug_type($value).' given.');

        (new HashTransformer())->transform($value, $this->resolve(['algo' => 'md5']));
    }

    public function testUnknownAlgorithm(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolve(['algo' => 'unknown']);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolve(array $options): array
    {
        $resolver = new OptionsResolver();
        (new HashTransformer())->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
