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

namespace CleverAge\ProcessBundle\Tests\Transformer\Array;

use CleverAge\ProcessBundle\Transformer\Array\ArrayFilterTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(ArrayFilterTransformer::class)]
class ArrayFilterTransformerTest extends TestCase
{
    public function testWholeValueConditionOnScalars(): void
    {
        self::assertSame([0 => 'a', 2 => 'a'], $this->filter(['a', 'b', 'a'], ['match' => ['' => 'a']]));
        self::assertSame([1 => 'b'], $this->filter(['a', 'b'], ['not_match' => ['' => 'a']]));
        self::assertSame([1 => 'b'], $this->filter(['a', 'b'], ['match_regexp' => ['' => '/^b$/']]));
        self::assertSame([1 => 'b'], $this->filter(['', 'b'], ['not_empty' => ['' => null]]));
    }

    public function testPropertyPathOnScalarIsNull(): void
    {
        self::assertSame([], $this->filter(['a', 'b'], ['match' => ['[key]' => 'a']]));
        self::assertSame(['a', 'b'], $this->filter(['a', 'b'], ['empty' => ['[key]' => null]]));
    }

    public function testConditionOnArrays(): void
    {
        $items = [['type' => 'product'], ['type' => 'category'], ['other' => 'x']];

        self::assertSame([0 => ['type' => 'product']], $this->filter($items, ['match' => ['[type]' => 'product']]));
        self::assertSame([2 => ['other' => 'x']], $this->filter($items, ['empty' => ['[type]' => null]]));
    }

    /**
     * @param array<mixed>         $value
     * @param array<string, mixed> $condition
     *
     * @return array<mixed>
     */
    private function filter(array $value, array $condition): array
    {
        $transformer = new ArrayFilterTransformer(PropertyAccess::createPropertyAccessor());
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $transformer->transform($value, $resolver->resolve(['condition' => $condition]));
    }
}
