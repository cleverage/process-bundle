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

use CleverAge\ProcessBundle\Transformer\DebugTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\VarDumper;

#[\PHPUnit\Framework\Attributes\CoversClass(DebugTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(DebugTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(DebugTransformer::class, 'getCode')]
class DebugTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $transformer = new DebugTransformer();
        $value = 123;

        $transformedValue = $transformer->transform($value);

        $this->assertSame($value, $transformedValue);

        if (class_exists(VarDumper::class)) {
            VarDumper::dump($value);
        }
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new DebugTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('dump', $code);
    }
}
