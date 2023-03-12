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
 * Test suite for CallbackTransformerTest
 */
class CallbackTransformerTest extends AbstractProcessTest
{
    public static function doCallback(): string
    {
        return implode('-', func_get_args());
    }

    /**
     * Assert data is correctly filtered
     */
    public function testSimpleCallback(): void
    {
        $input = '3';

        $result = $this->processManager->execute('test.callback_transformer.simple', $input);

        self::assertEquals('3', $result);
    }

    /**
     * Assert data is correctly filtered
     */
    public function testLeftParametersCallback(): void
    {
        $input = '3';

        $result = $this->processManager->execute('test.callback_transformer.left_parameters', $input);

        self::assertEquals('1-2-3', $result);
    }

    /**
     * Assert data is correctly filtered
     */
    public function testRightParametersCallback(): void
    {
        $input = '3';

        $result = $this->processManager->execute('test.callback_transformer.right_parameters', $input);

        self::assertEquals('3-4-5', $result);
    }

    /**
     * Assert data is correctly filtered
     */
    public function testAdditionalParametersCallback(): void
    {
        $input = '6';

        $result = $this->processManager->execute('test.callback_transformer.additional_parameters', $input);

        self::assertEquals('6-7-8', $result);
    }

    /**
     * Assert data is correctly filtered
     */
    public function testLeftAndRightParametersCallback(): void
    {
        $input = '3';

        $result = $this->processManager->execute('test.callback_transformer.left_right_parameters', $input);

        self::assertEquals('1-2-3-4-5', $result);
    }
}
