<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Test suite for ArrayFilterTransformer
 */
class ArrayFilterTransformerTest extends AbstractProcessTest
{
    /**
     * Assert data is correctly filtered
     */
    public function testSimpleFilter()
    {
        $input = [
            ['data' => 1, 'filter_value' => 'X'],
            ['data' => 2, 'filter_value' => 'Y'],
            ['data' => 3],
            ['data' => 4, 'filter_value' => 'X'],
            ['data' => 5, 'filter_value' => 'Y'],
            ['data' => 6],
        ];

        $result = $this->processManager->execute('test.array_filter_transformer.simple', $input);

        $nativeResult = array_filter(
            $input,
            static function ($item) {
                return isset($item['filter_value']) && 'X' === $item['filter_value'];
            }
        );

        // Note that to match native function, key are preserved
        $expectedResult = [
            0 => ['data' => 1, 'filter_value' => 'X'],
            3 => ['data' => 4, 'filter_value' => 'X'],
        ];

        self::assertCount(2, $result);
        self::assertEquals($expectedResult, $result);
        self::assertEquals($nativeResult, $result);
    }
}
