<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Tests for the TransformerTask
 */
class TransformerTaskTest extends AbstractProcessTest
{

    /**
     * Assert a simple transformation, from one array to another
     */
    public function testSimpleMapping()
    {
        $result = $this->processManager->execute('test.transformer_task.simple', 'value');

        self::assertEquals(['field' => 'value'], $result);
    }

    /**
     * Assert that if "ignore_missing" is false, then an error is thrown for missing fields
     *
     * @expectedException \RuntimeException
     */
    public function testMissingMapping()
    {
        $this->processManager->execute('test.transformer_task.error', 'value');
    }

    /**
     * Assert we can use multiple times the same sub-transformer using # suffixes
     */
    public function testMultiSubtransformers()
    {
        $result = $this->processManager->execute('test.transformer_task.multi_subtransformers', [3, null, 4, 2]);

        self::assertEquals(['field' => [2, 4, 3]], $result);
    }
}
