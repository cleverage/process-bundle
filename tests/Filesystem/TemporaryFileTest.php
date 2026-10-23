<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Filesystem;

use CleverAge\ProcessBundle\Filesystem\TemporaryFile;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(TemporaryFile::class)]
class TemporaryFileTest extends TestCase
{
    public function testAnEmptyFileReadableByItsOwnerOnlyIsCreated(): void
    {
        $path = TemporaryFile::create('temporary_file_test_', '.csv');
        try {
            self::assertMatchesRegularExpression(
                '#^'.preg_quote(sys_get_temp_dir(), '#').'/temporary_file_test_[0-9a-f]{32}\.csv$#',
                $path
            );
            self::assertFileExists($path);
            self::assertSame(0, filesize($path));
            self::assertSame(0o600, fileperms($path) & 0o777);
        } finally {
            unlink($path);
        }
    }

    public function testEachFileHasItsOwnName(): void
    {
        $first = TemporaryFile::create('temporary_file_test_');
        $second = TemporaryFile::create('temporary_file_test_');
        unlink($first);
        unlink($second);

        self::assertNotSame($first, $second);
    }
}
