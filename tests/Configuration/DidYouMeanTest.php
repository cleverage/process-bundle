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

namespace CleverAge\ProcessBundle\Tests\Configuration;

use CleverAge\ProcessBundle\Configuration\DidYouMean;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(DidYouMean::class)]
class DidYouMeanTest extends TestCase
{
    /**
     * @return iterable<string, array{string, list<string>, string}>
     */
    public static function provideSuggestions(): iterable
    {
        $codes = ['demo.main', 'demo.mail', 'uppercase', 'json-stream', 'dump'];
        yield 'typo' => ['demo.mian', $codes, ' Did you mean "demo.main"?'];
        yield 'closest first' => ['demo.mai', $codes, ' Did you mean "demo.main"?'];
        yield 'missing letter' => ['uppercas', $codes, ' Did you mean "uppercase"?'];
        yield 'case' => ['UPPERCASE', $codes, ' Did you mean "uppercase"?'];
        yield 'too far' => ['csv', $codes, ''];
        yield 'short value' => ['jsn', $codes, ''];
        yield 'no candidate' => ['demo.main', [], ''];
    }

    /**
     * @param list<string> $candidates
     */
    #[DataProvider('provideSuggestions')]
    public function testSuggest(string $value, array $candidates, string $expected): void
    {
        self::assertSame($expected, DidYouMean::suggest($value, $candidates));
    }
}
