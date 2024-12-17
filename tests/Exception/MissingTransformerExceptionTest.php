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

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CleverAge\ProcessBundle\Exception\MissingTransformerException
 */
class MissingTransformerExceptionTest extends TestCase
{
    /**
     * @covers ::create
     */
    public function testCreate(): void
    {
        $exception = MissingTransformerException::create('my_transformer');

        $this->assertInstanceOf(\UnexpectedValueException::class, $exception);
        $this->assertEquals('No transformer with code : my_transformer', $exception->getMessage());
    }
}
