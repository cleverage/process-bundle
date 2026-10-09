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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(FileReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class FileReaderTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('file_reader_test_', true);
        (new Filesystem())->dumpFile($this->tmpDir.'/sample.txt', "line 1\nline 2\n");
    }

    protected function tearDown(): void
    {
        $filesystem = new Filesystem();
        $filesystem->chmod($this->tmpDir, 0o755, 0o000, true);
        $filesystem->remove($this->tmpDir);
    }

    public function testOutputsWholeFileContent(): void
    {
        $state = $this->execute(['filename' => $this->tmpDir.'/sample.txt']);

        self::assertSame("line 1\nline 2\n", $state->getOutput());
    }

    public function testIgnoresInput(): void
    {
        $state = $this->execute(['filename' => $this->tmpDir.'/sample.txt'], $this->tmpDir.'/other.txt');

        self::assertSame("line 1\nline 2\n", $state->getOutput());
    }

    public function testOutputsEmptyStringForEmptyFile(): void
    {
        (new Filesystem())->dumpFile($this->tmpDir.'/empty.txt', '');

        $state = $this->execute(['filename' => $this->tmpDir.'/empty.txt']);

        self::assertSame('', $state->getOutput());
    }

    public function testThrowsWhenFileDoesNotExist(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("File does not exists: '{$this->tmpDir}/missing.txt'");

        $this->execute(['filename' => $this->tmpDir.'/missing.txt']);
    }

    public function testThrowsWhenFileIsNotReadable(): void
    {
        $filename = $this->tmpDir.'/sample.txt';
        chmod($filename, 0o000);
        clearstatcache();
        if (is_readable($filename)) {
            self::markTestSkipped('File permissions are not enforced for the current user (root?)');
        }

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("File is not readable: '{$filename}'");

        $this->execute(['filename' => $filename]);
    }

    public function testThrowsOnMissingFilenameOption(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->execute([]);
    }

    public function testThrowsOnInvalidFilenameOptionType(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->execute(['filename' => 42]);
    }

    private function execute(array $options, mixed $input = null): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('read', FileReaderTask::class, $options));

        $task = new FileReaderTask();
        $task->initialize($state);
        $state->setInput($input);
        $task->execute($state);

        return $state;
    }
}
