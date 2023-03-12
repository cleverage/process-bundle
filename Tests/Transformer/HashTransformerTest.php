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
 * Tests for Hash transformer
 */
class HashTransformerTest extends AbstractProcessTest
{
    /**
     * Assert a string can be hash in md5
     */
    public function testMd5Hash(): void
    {
        $result = $this->processManager->execute('test.hash_transformer.md5', 'This is a string');
        self::assertEquals('41fb5b5ae4d57c5ee528adb00e5e8e74', $result);
    }

    /**
     * Assert a string can be hash in sha512
     */
    public function testSha512Hash(): void
    {
        $result = $this->processManager->execute('test.hash_transformer.sha512', 'This is a string');
        self::assertEquals(
            'f4d54d32e3523357ff023903eaba2721e8c8cfc7702663782cb3e52faf2c56c002cc3096b5f2b6df870be665d0040e9963590eb02d03d166e52999cd1c430db1',
            $result
        );
    }
}
