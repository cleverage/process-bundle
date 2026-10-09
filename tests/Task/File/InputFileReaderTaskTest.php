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
use CleverAge\ProcessBundle\Task\File\FileReaderTask;
use CleverAge\ProcessBundle\Task\File\InputFileReaderTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(InputFileReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FileReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class InputFileReaderTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('input_file_reader_test_', true);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->tmpDir.'/a.txt', 'content A');
        $filesystem->dumpFile($this->tmpDir.'/b.txt', 'content B');
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->chmod($this->tmpDir, 0o755, 0o000, true);
        $filesystem->remove($this->tmpDir);
    }

    public function testOutputsContentOfFileGivenAsInput(): void
    {
        $task = new InputFileReaderTask();
        $state = $this->createState([]);
        $task->initialize($state);

        self::assertSame('content A', $this->read($task, $state, $this->tmpDir.'/a.txt'));
    }

    public function testReadsDifferentFilesSuccessively(): void
    {
        $task = new InputFileReaderTask();
        $state = $this->createState([]);
        $task->initialize($state);

        self::assertSame(
            ['content A', 'content B', 'content A'],
            [
                $this->read($task, $state, $this->tmpDir.'/a.txt'),
                $this->read($task, $state, $this->tmpDir.'/b.txt'),
                $this->read($task, $state, $this->tmpDir.'/a.txt'),
            ],
        );
    }

    public function testThrowsWhenInputFileDoesNotExist(): void
    {
        $task = new InputFileReaderTask();
        $state = $this->createState([]);
        $task->initialize($state);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("File does not exists: '{$this->tmpDir}/missing.txt'");

        $this->read($task, $state, $this->tmpDir.'/missing.txt');
    }

    public function testThrowsWhenInputFileIsNotReadable(): void
    {
        $filename = $this->tmpDir.'/a.txt';
        chmod($filename, 0o000);
        clearstatcache();
        if (is_readable($filename)) {
            self::markTestSkipped('File permissions are not enforced for the current user (root?)');
        }
        $task = new InputFileReaderTask();
        $state = $this->createState([]);
        $task->initialize($state);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("File is not readable: '{$filename}'");

        $this->read($task, $state, $filename);
    }

    public function testRejectsFilenameOption(): void
    {
        $task = new InputFileReaderTask();
        $state = $this->createState(['filename' => $this->tmpDir.'/a.txt']);

        $this->expectException(UndefinedOptionsException::class);

        $task->initialize($state);
    }

    private function read(InputFileReaderTask $task, ProcessState $state, mixed $input): mixed
    {
        $state->setInput($input);
        $task->execute($state);

        return $state->getOutput();
    }

    /**
     * @param array<string, mixed> $options
     */
    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('read', InputFileReaderTask::class, $options));

        return $state;
    }
}
