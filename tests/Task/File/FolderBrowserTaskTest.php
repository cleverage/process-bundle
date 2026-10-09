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
use CleverAge\ProcessBundle\Task\File\FolderBrowserTask;
use CleverAge\ProcessBundle\Task\File\InputFolderBrowserTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

#[\PHPUnit\Framework\Attributes\CoversClass(FolderBrowserTask::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(InputFolderBrowserTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class FolderBrowserTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('folder_browser_test_', true);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->tmpDir.'/dirA/a1.txt', 'a1');
        $filesystem->dumpFile($this->tmpDir.'/dirA/a2.txt', 'a2');
        $filesystem->dumpFile($this->tmpDir.'/dirB/b1.txt', 'b1');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testSameFolderIsBrowsedAgainOnSecondExecution(): void
    {
        $task = new FolderBrowserTask(new NullLogger());
        $state = $this->createState(['folder_path' => $this->tmpDir.'/dirA']);

        $expected = [$this->tmpDir.'/dirA/a1.txt', $this->tmpDir.'/dirA/a2.txt'];
        self::assertSame(
            [$expected, $expected],
            [$this->iterate($task, $state), $this->iterate($task, $state)],
        );
    }

    public function testInputFolderBrowserBrowsesSameFolderTwice(): void
    {
        $task = new InputFolderBrowserTask(new NullLogger());
        $state = $this->createState([]);

        $expected = [$this->tmpDir.'/dirA/a1.txt', $this->tmpDir.'/dirA/a2.txt'];
        self::assertSame(
            [$expected, $expected],
            [$this->iterate($task, $state, $this->tmpDir.'/dirA'), $this->iterate($task, $state, $this->tmpDir.'/dirA')],
        );
    }

    public function testInputFolderBrowserBrowsesDifferentFoldersSuccessively(): void
    {
        $task = new InputFolderBrowserTask(new NullLogger());
        $state = $this->createState([]);

        self::assertSame(
            [$this->tmpDir.'/dirA/a1.txt', $this->tmpDir.'/dirA/a2.txt'],
            $this->iterate($task, $state, $this->tmpDir.'/dirA'),
        );
        self::assertSame([$this->tmpDir.'/dirB/b1.txt'], $this->iterate($task, $state, $this->tmpDir.'/dirB'));
    }

    public function testInputFolderBrowserBrowsesAnotherFolderAfterAnEmptyOne(): void
    {
        (new Filesystem())->mkdir($this->tmpDir.'/empty');
        $task = new InputFolderBrowserTask(new NullLogger());
        $state = $this->createState([]);

        self::assertSame([], $this->iterate($task, $state, $this->tmpDir.'/empty'));
        self::assertSame([$this->tmpDir.'/dirB/b1.txt'], $this->iterate($task, $state, $this->tmpDir.'/dirB'));
    }

    public function testInputFolderBrowserRequiresAFolderPathAsInput(): void
    {
        $task = new InputFolderBrowserTask(new NullLogger());
        $state = $this->createState([]);
        $task->initialize($state);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('No folder path given as input');

        $this->iterate($task, $state);
    }

    /**
     * Mimics the ProcessManager loop over an iterable task and returns the non-skipped outputs.
     *
     * @return list<mixed>
     */
    private function iterate(FolderBrowserTask $task, ProcessState $state, mixed $input = null): array
    {
        $outputs = [];
        $state->setInput($input);
        do {
            $state->setSkipped(false);
            $task->execute($state);
            if (!$state->isSkipped()) {
                $outputs[] = $state->getOutput();
            }
        } while ($task->next($state));

        return $outputs;
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
        $state->setTaskConfiguration(new TaskConfiguration('browse', FolderBrowserTask::class, $options));

        return $state;
    }
}
