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

namespace CleverAge\ProcessBundle\Tests\Transformer\String;

use CleverAge\ProcessBundle\Transformer\String\SprintfTransformer;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(SprintfTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(SprintfTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(SprintfTransformer::class, 'getCode')]
class SprintfTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $sprintfTransformer = new SprintfTransformer();
        $result = $sprintfTransformer->transform(['bar'], ['format' => 'foo %s']);
        $this->assertEquals('foo bar', $result);

        $result = $sprintfTransformer->transform('bar', ['format' => 'foo %s']);
        $this->assertEquals('foo bar', $result);
    }

    public function testCode(): void
    {
        $trimTransformer = new SprintfTransformer();
        $result = $trimTransformer->getCode();
        $this->assertEquals('sprintf', $result);
    }
}
