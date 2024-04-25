<?php

declare(strict_types=1);

namespace Transformer;

use CleverAge\ProcessBundle\Transformer\SprintfTransformer;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \CleverAge\ProcessBundle\Transformer\SprintfTransformer
 */
class SprintfTransformerTest extends TestCase
{
    /**
     * @covers ::transform
     */
    public function testTransform(): void
    {
        $sprintfTransformer = new SprintfTransformer();
        $result = $sprintfTransformer->transform(['bar'], ['format' => 'foo %s']);
        $this->assertEquals('foo bar', $result);

        $result = $sprintfTransformer->transform('bar', ['format' => 'foo %s']);
        $this->assertEquals('foo bar', $result);
    }

    /**
     * @covers ::getCode
     */
    public function testCode(): void
    {
        $trimTransformer = new SprintfTransformer();
        $result = $trimTransformer->getCode();
        $this->assertEquals('sprintf', $result);
    }
}
