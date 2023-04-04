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
 * Tests for type setter transformer
 */
class TypeSetterTransformerTest extends AbstractProcessTest
{
    /**
     * Assert int to int convertion
     */
    public function testIntToInt(): void
    {
        $result = $this->processManager->execute('test.type_setter_transformer.int_to_int', 1);
        self::assertSame(1, $result);
    }

    /**
     * Assert string to int convertion
     */
    public function testStringToInt(): void
    {
        $result = $this->processManager->execute('test.type_setter_transformer.string_to_int', '1');
        self::assertSame(1, $result);
    }

    /**
     * Assert int to string convertion
     */
    public function testIntToString(): void
    {
        $result = $this->processManager->execute('test.type_setter_transformer.int_to_string', 1);
        self::assertSame('1', $result);
    }
}
