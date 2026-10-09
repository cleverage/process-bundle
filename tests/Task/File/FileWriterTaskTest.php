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
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\FileWriterTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(FileWriterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class FileWriterTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('file_writer_test_', true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testWritesInputAndOutputsFilename(): void
    {
        $filename = $this->tmpDir.'/result.txt';
        $task = new FileWriterTask();
        $state = $this->createState(['filename' => $filename]);
        $task->initialize($state);

        $this->write($task, $state, "hello\nworld");

        self::assertSame($filename, $state->getOutput());
        self::assertSame("hello\nworld", file_get_contents($filename));
    }

    public function testCreatesMissingParentDirectories(): void
    {
        $filename = $this->tmpDir.'/sub/dir/result.txt';
        $task = new FileWriterTask();
        $state = $this->createState(['filename' => $filename]);
        $task->initialize($state);

        $this->write($task, $state, 'content');

        self::assertSame('content', file_get_contents($filename));
    }

    public function testEachInputOverwritesTheFile(): void
    {
        $filename = $this->tmpDir.'/result.txt';
        (new Filesystem())->dumpFile($filename, 'previous content');
        $task = new FileWriterTask();
        $state = $this->createState(['filename' => $filename]);
        $task->initialize($state);

        $this->write($task, $state, 'first');
        self::assertSame('first', file_get_contents($filename));

        $this->write($task, $state, 'second');
        self::assertSame('second', file_get_contents($filename));
    }

    public function testThrowsOnMissingFilenameOption(): void
    {
        $task = new FileWriterTask();
        $state = $this->createState([]);

        $this->expectException(MissingOptionsException::class);

        $task->initialize($state);
    }

    public function testThrowsOnInvalidFilenameOptionType(): void
    {
        $task = new FileWriterTask();
        $state = $this->createState(['filename' => ['result.txt']]);

        $this->expectException(InvalidOptionsException::class);

        $task->initialize($state);
    }

    private function write(FileWriterTask $task, ProcessState $state, mixed $input): void
    {
        $state->setInput($input);
        $task->execute($state);
    }

    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('write', FileWriterTask::class, $options));

        return $state;
    }
}
