<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Transformer;

use CleverAge\ProcessBundle\Transformer\DebugTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\VarDumper\VarDumper;

class DebugTransformerTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DebugTransformer::transform
     */
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

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\DebugTransformer::getCode
     */
    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new DebugTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('dump', $code);
    }
}
