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

namespace CleverAge\ProcessBundle\Tests\Task\File;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\FileRemoverTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[\PHPUnit\Framework\Attributes\CoversClass(FileRemoverTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class FileRemoverTaskTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('file_remover_test_', true);
        $this->filesystem = new Filesystem();
        $this->filesystem->dumpFile($this->tmpDir.'/a.txt', 'A');
        $this->filesystem->dumpFile($this->tmpDir.'/b.txt', 'B');
        $this->filesystem->dumpFile($this->tmpDir.'/dir/sub/c.txt', 'C');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testRemovesFileGivenAsInput(): void
    {
        $state = $this->execute($this->tmpDir.'/a.txt');

        self::assertFileDoesNotExist($this->tmpDir.'/a.txt');
        self::assertFileExists($this->tmpDir.'/b.txt');
        self::assertNull($state->getOutput());
    }

    public function testRemovesDirectoryRecursively(): void
    {
        $this->execute($this->tmpDir.'/dir');

        self::assertDirectoryDoesNotExist($this->tmpDir.'/dir');
        self::assertFileExists($this->tmpDir.'/a.txt');
    }

    public function testRemovesEveryPathOfAList(): void
    {
        $this->execute([$this->tmpDir.'/a.txt', $this->tmpDir.'/dir']);

        self::assertFileDoesNotExist($this->tmpDir.'/a.txt');
        self::assertDirectoryDoesNotExist($this->tmpDir.'/dir');
        self::assertFileExists($this->tmpDir.'/b.txt');
    }

    public function testIgnoresMissingPath(): void
    {
        $state = $this->execute($this->tmpDir.'/missing.txt');

        self::assertFileExists($this->tmpDir.'/a.txt');
        self::assertNull($state->getOutput());
    }

    private function execute(mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setInput($input);

        (new FileRemoverTask())->execute($state);

        return $state;
    }
}
