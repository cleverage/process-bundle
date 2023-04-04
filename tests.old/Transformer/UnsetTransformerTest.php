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

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Tests for the UnsetTransformer
 */
class UnsetTransformerTest extends AbstractProcessTest
{
    /**
     * Assert the transformer can do a simple unset
     */
    public function testSimpleUnset(): void
    {
        $input = [
            'other' => 1,
            'to_unset' => 1,
            'to_test' => 2,
        ];
        $result = $this->processManager->execute('test.unset_transformer.simple', $input);
        self::assertEquals([
            'other' => 1,
            'to_test' => 2,
        ], $result);
    }

    /**
     * Assert a few simple condition can trigger unset (or not)
     */
    public function testConditionalUnset(): void
    {
        $input = [
            'other' => 1,
            'to_unset' => 1,
            'to_test' => 2,
        ];

        // Should unset
        $result = $this->processManager->execute('test.unset_transformer.condition', $input);
        self::assertEquals([
            'other' => 1,
            'to_test' => 2,
        ], $result);

        // No unset
        $input['to_test'] = 3;
        $result = $this->processManager->execute('test.unset_transformer.condition', $input);
        self::assertEquals([
            'other' => 1,
            'to_unset' => 1,
            'to_test' => 3,
        ], $result);

        // Checking null, no unset
        $result = $this->processManager->execute('test.unset_transformer.condition_null', $input);
        self::assertEquals([
            'other' => 1,
            'to_unset' => 1,
            'to_test' => 3,
        ], $result);

        // Should unset
        $input['to_test'] = null;
        $result = $this->processManager->execute('test.unset_transformer.condition_null', $input);
        self::assertEquals([
            'other' => 1,
            'to_test' => null,
        ], $result);
    }

    /**
     * Assert the transformer detect wrong types
     *
     * @expectedException \RuntimeException
     */
    public function testWrongUnsetString(): void
    {
        $this->processManager->execute('test.unset_transformer.simple', 'not an array');
    }

    /**
     * Assert the transformer detect wrong values
     *
     * @expectedException \RuntimeException
     */
    public function testWrongUnsetMissingProperty(): void
    {
        $this->processManager->execute('test.unset_transformer.simple', ['no property found']);
    }
}
