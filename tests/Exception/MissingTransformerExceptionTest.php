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

namespace Exception;

use CleverAge\ProcessBundle\Configuration\DidYouMean;
use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(MissingTransformerException::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(MissingTransformerException::class, 'create')]
#[\PHPUnit\Framework\Attributes\UsesClass(DidYouMean::class)]
class MissingTransformerExceptionTest extends TestCase
{
    public function testCreate(): void
    {
        $exception = MissingTransformerException::create('my_transformer');

        $this->assertInstanceOf(\UnexpectedValueException::class, $exception);
        $this->assertEquals('No transformer with code : my_transformer', $exception->getMessage());
    }

    public function testCreateSuggestsTheClosestCode(): void
    {
        $exception = MissingTransformerException::create('uppercas', ['lowercase', 'uppercase']);

        $this->assertSame('No transformer with code : uppercas Did you mean "uppercase"?', $exception->getMessage());
    }
}
