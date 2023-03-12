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
 * Tests for the MappingTransformer
 */
class MappingTransformerTest extends AbstractProcessTest
{
    /**
     * Assert a simple mapping transformation, from one array to another
     */
    public function testSimpleMapping(): void
    {
        $result = $this->processManager->execute('test.mapping_transformer.simple', [
            'field' => 'value',
        ]);

        self::assertEquals([
            'field2' => 'value',
        ], $result);
    }

    /**
     * Assert that if "ignore_missing" is false, then an error is thrown for missing fields
     *
     * @expectedException \RuntimeException
     */
    public function testMissingMapping(): void
    {
        $this->processManager->execute('test.mapping_transformer.error', [
            'field' => 'value',
        ]);
    }

    /**
     * Assert we can use multiple times the same sub-transformer using # suffixes
     */
    public function testMultiSubtransformers(): void
    {
        $result = $this->processManager->execute(
            'test.mapping_transformer.multi_subtransformers',
            [
                'field' => [3, null, 4, 2],
            ]
        );

        self::assertEquals([
            'field2' => [2, 4, 3],
        ], $result);
    }

    /**
     * Assert we can use a deep property path as a key to generate a multi-depth array
     */
    public function testDeepMapping(): void
    {
        $result = $this->processManager->execute('test.mapping_transformer.deep_mapping', [
            'value' => 'ok',
        ]);

        self::assertEquals([
            'field1' => [
                'field2' => [
                    'field3' => 'ok',
                ],
            ],
        ], $result);
    }

    /**
     * Test the '.' source property path
     */
    public function testFullInput(): void
    {
        $result = $this->processManager->execute('test.mapping_transformer.full_input', [
            'value' => 'ok',
        ]);

        self::assertEquals([
            'out' => [
                'value' => 'ok',
            ],
        ], $result);
    }

    /**
     * Test the '.' source property path inside an array of source codes
     */
    public function testFullInputInArray(): void
    {
        $result = $this->processManager->execute('test.mapping_transformer.full_input_in_array', [
            'field' => 'ok',
        ]);

        self::assertEquals([
            'out' => [
                'some_field' => 'ok',
                'full' => [
                    'field' => 'ok',
                ],
            ],
        ], $result);
    }

    /**
     * Test that a source property can be an array with numeric keys (see commit e141cb61)
     */
    public function testMultiSourceFieldInSequence(): void
    {
        $result = $this->processManager->execute('test.mapping_transformer.multi_source_field_in_sequence', [
            'field1' => 'a',
            'field2' => 'b',
            'field3' => 'c',
        ]);

        self::assertEquals([
            'out' => ['a', 'b', 'c'],
        ], $result);
    }
}
