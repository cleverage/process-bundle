<?php

declare(strict_types=1);

namespace Transformer;

use CleverAge\ProcessBundle\Transformer\TrimTransformer;
use PHPUnit\Framework\TestCase;

class TrimTransformerTest extends TestCase
{

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\TrimTransformer::transform
     */
    public function testTrim(): void
    {
        $trimTransformer = new TrimTransformer();
        $result = $trimTransformer->transform('     test 1 ');
        $this->assertEquals('test 1', $result);

        $result = $trimTransformer->transform(null);
        $this->assertNull($result);
    }

    /**
     * @covers \CleverAge\ProcessBundle\Transformer\TrimTransformer::getCode
     */
    public function testCode(): void
    {
        $trimTransformer = new TrimTransformer();
        $result = $trimTransformer->getCode();
        $this->assertEquals('trim', $result);
    }
}