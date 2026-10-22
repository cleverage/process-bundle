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

namespace CleverAge\ProcessBundle\Tests\Context;

use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContextualOptionResolver::class)]
class ContextualOptionResolverTest extends TestCase
{
    public function testExactMatchReturnsTheContextValue(): void
    {
        self::assertSame(['a', 'b'], (new ContextualOptionResolver())->contextualizeOption('{{ ids }}', ['ids' => ['a', 'b']]));
    }

    public function testPlaceholdersAreReplacedInStrings(): void
    {
        self::assertSame(
            ['path' => 'var/data/books.csv', 'other' => 'no placeholder', 'books' => 1],
            (new ContextualOptionResolver())->contextualizeOptions(
                ['path' => 'var/{{dir}}/{{ name }}.csv', 'other' => 'no placeholder', '{{ name }}' => 1],
                ['dir' => 'data', 'name' => 'books']
            )
        );
    }

    /**
     * The context keys are free (console, process_executor, UI...): they must not alter the pattern.
     */
    public function testContextKeysAreQuoted(): void
    {
        $resolver = new ContextualOptionResolver();

        // A "/" made the pattern invalid, for every string option
        self::assertSame('value', $resolver->contextualizeOption('{{ a/b }}', ['a/b' => 'value']));
        self::assertSame('no placeholder', $resolver->contextualizeOption('no placeholder', ['a/b' => 'value']));
        // "x." matched "{{ x }}"
        self::assertSame('{{ x }}', $resolver->contextualizeOption('{{ x }}', ['x.' => 'value']));
        self::assertSame('value', $resolver->contextualizeOption('{{ x. }}', ['x.' => 'value']));
    }

    public function testEmptyContext(): void
    {
        // "{{ }}" matched an empty key
        self::assertSame('{{ }} {{ name }}', (new ContextualOptionResolver())->contextualizeOption('{{ }} {{ name }}', []));
    }

    public function testNonStringValuesAreKept(): void
    {
        self::assertSame(12, (new ContextualOptionResolver())->contextualizeOption(12, ['name' => 'books']));
    }
}
