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

use CleverAge\ProcessBundle\Transformer\PregFilterTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(PregFilterTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(PregFilterTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(PregFilterTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(PregFilterTransformer::class, 'getCode')]
class PregFilterTransformerTest extends TestCase
{
    public function testTransformWithStringReplacement(): void
    {
        $transformer = new PregFilterTransformer();
        $options = $this->resolveOptions($transformer, [
            'pattern' => '/^(\d{2})\/(\d{2})\/(\d{4})$/',
            'replacement' => '$3-$2-$1',
        ]);

        $this->assertSame('2019-12-02', $transformer->transform('02/12/2019', $options));
    }

    public function testTransformReturnsNullWhenPatternDoesNotMatch(): void
    {
        $transformer = new PregFilterTransformer();
        $options = $this->resolveOptions($transformer, [
            'pattern' => '/^\d+$/',
            'replacement' => 'number',
        ]);

        $this->assertNull($transformer->transform('abc', $options));
    }

    public function testTransformWithArrayReplacement(): void
    {
        $transformer = new PregFilterTransformer();
        $options = $this->resolveOptions($transformer, [
            'pattern' => ['/a/', '/b/'],
            'replacement' => ['1', '2'],
        ]);

        $this->assertSame('12c', $transformer->transform('abc', $options));
    }

    public function testArrayReplacementRequiresArrayPattern(): void
    {
        $transformer = new PregFilterTransformer();

        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($transformer, [
            'pattern' => '/a/',
            'replacement' => ['1', '2'],
        ]);
    }

    public function testGetCode(): void
    {
        $this->assertSame('preg_filter', (new PregFilterTransformer())->getCode());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(PregFilterTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
