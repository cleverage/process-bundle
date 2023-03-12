<?php declare(strict_types=1);
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

class RulesTransformerTest extends AbstractProcessTest
{

    /**
     * Assert basic rules types
     *
     * @throws \Exception
     */
    public function testSimpleRule()
    {
        $result1 = $this->processManager->execute('test.rules_transformer.simple', 'ok');
        self::assertEquals('result1', $result1);

        $result2 = $this->processManager->execute('test.rules_transformer.simple', 'ko');
        self::assertEquals('result2', $result2);

        $result3 = $this->processManager->execute('test.rules_transformer.simple', 'any');
        self::assertEquals('result3', $result3);
    }

}
