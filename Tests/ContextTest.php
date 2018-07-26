<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests;

class ContextTest extends AbstractProcessTest
{

    /**
     * Assert a value can correctly by passed through context
     */
    public function testSimpleContext()
    {
        $result = $this->processManager->execute('test.context', 'ko', ['value' => 'ok']);

        self::assertEquals('ok', $result);
    }
}
