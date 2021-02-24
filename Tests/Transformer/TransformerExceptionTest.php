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

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

class TransformerExceptionTest extends AbstractProcessTest
{
    /**
     * Basic test cas with a simple error chain
     */
    public function testErrorMessageChain()
    {
        $origException = new \Exception('OriginalError');
        $transformerException = $origException;

        for ($i = 0; $i < 10; $i++) {
            $transformerException = new TransformerException("sub_transformer_{$i}", 0, $transformerException);
        }

        self::assertContains('OriginalError', $transformerException->getMessage());
    }

    /**
     * Simple test case using a simulated error inside a mapping transformer and array_map transformers
     */
    public function testDeepError()
    {
        $input = [
            'field' => [
                [
                    ['a', 'b'],
                    1, // Error here
                ],
            ],
        ];

        $message = null;
        try {
            $this->processManager->execute('test.transformer_exception.deep', $input);
        } catch (\RuntimeException $exception) {
            $message = $exception->getMessage();
        }

        self::assertNotNull($message);
        self::assertContains("For target property '1', transformation 'implode' have failed", $message);
    }
}
