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

use CleverAge\ProcessBundle\Transformer\UnavailableTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnavailableTransformer::class)]
class UnavailableTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('normalize', (new UnavailableTransformer('normalize', 'message'))->getCode());
    }

    public function testTransformThrowsTheMessage(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The serializer is not enabled');

        (new UnavailableTransformer('normalize', 'The serializer is not enabled'))->transform('value');
    }
}
