<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task;

use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Tests for the ValidatorTask
 */
class ValidatorTaskTest extends AbstractProcessTest
{
    /**
     * Assert the input is Valid and no error is thrown
     */
    public function testSimpleValidation()
    {
        $input = [
            'int_field' => 42,
            'any_field' => 'hello',
            'choice_field' => 'Some random value 1',
        ];
        $result = $this->processManager->execute('test.validator_task', $input);
        self::assertEquals($input, $result);
    }

}
