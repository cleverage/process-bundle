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

use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(JsonStreamFile::class)]
class JsonStreamFileTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/'.uniqid('json_stream_file_test_', true);
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tmpDir);
    }

    public function testReadLineDecodesObjectsAndArrays(): void
    {
        $file = new JsonStreamFile($this->createFile("{\"a\":1}\n[1,2]\n"));

        self::assertSame(['a' => 1], $file->readLine());
        self::assertSame([1, 2], $file->readLine());
        self::assertNull($file->readLine());
    }

    public function testReadLineReturnsNullForNullLine(): void
    {
        $file = new JsonStreamFile($this->createFile("null\n"));

        self::assertNull($file->readLine());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function scalarLineProvider(): iterable
    {
        yield 'int' => ['42', 'int'];
        yield 'float' => ['4.2', 'float'];
        yield 'string' => ['"foo"', 'string'];
        yield 'bool' => ['true', 'bool'];
    }

    #[DataProvider('scalarLineProvider')]
    public function testReadLineThrowsOnScalarLine(string $line, string $type): void
    {
        $filename = $this->createFile("{\"a\":1}\n{$line}\n");
        $file = new JsonStreamFile($filename);
        $file->readLine();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(\sprintf('Line 2 of file "%s" must be a JSON object or array, got %s', $filename, $type));
        $file->readLine();
    }

    public function testWriteCreatesMissingParentDirectory(): void
    {
        $filename = $this->tmpDir.'/sub/dir/out.jsonl';

        $file = new JsonStreamFile($filename, 'wb');
        $file->writeLine(['a' => 1]);
        unset($file);

        self::assertSame('{"a":1}'.\PHP_EOL, file_get_contents($filename));
    }

    public function testReadDoesNotCreateMissingParentDirectory(): void
    {
        $filename = $this->tmpDir.'/missing/in.jsonl';

        try {
            new JsonStreamFile($filename);
            self::fail('Opening a missing file for reading should fail');
        } catch (\RuntimeException) {
        }

        self::assertDirectoryDoesNotExist(\dirname($filename));
    }

    private function createFile(string $content): string
    {
        $filename = $this->tmpDir.'/in.jsonl';
        file_put_contents($filename, $content);

        return $filename;
    }

    private function removeDirectory(string $dir): void
    {
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        ) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }
}
