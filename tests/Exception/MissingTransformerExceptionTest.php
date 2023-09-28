<?php

declare(strict_types=1);

namespace Exception;

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class MissingTransformerExceptionTest extends TestCase
{
    /**
     * @covers \CleverAge\ProcessBundle\Exception\MissingTransformerException::create
     */
    public function testCreate(): void
    {
        $exception = MissingTransformerException::create('my_transformer');

        $this->assertInstanceOf(UnexpectedValueException::class, $exception);
        $this->assertEquals('No transformer with code : my_transformer', $exception->getMessage());
    }
}
