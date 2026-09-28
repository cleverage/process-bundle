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
use CleverAge\ProcessBundle\Task\File\InputLineReaderTask;
use CleverAge\ProcessBundle\Task\File\LineReaderTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[\PHPUnit\Framework\Attributes\CoversClass(LineReaderTask::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(InputLineReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class LineReaderTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('line_reader_test_', true);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->tmpDir.'/a.txt', "a1\na2\n");
        $filesystem->dumpFile($this->tmpDir.'/b.txt', "b1\n");
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testSameFileIsReadAgainOnSecondExecution(): void
    {
        $task = new LineReaderTask();
        $state = $this->createState(['filename' => $this->tmpDir.'/a.txt']);

        self::assertSame(
            [["a1\n", "a2\n"], ["a1\n", "a2\n"]],
            [$this->iterate($task, $state), $this->iterate($task, $state)],
        );
    }

    public function testInputLineReaderReadsSameFileTwice(): void
    {
        $task = new InputLineReaderTask();
        $state = $this->createState([]);

        self::assertSame(
            [["a1\n", "a2\n"], ["a1\n", "a2\n"]],
            [$this->iterate($task, $state, $this->tmpDir.'/a.txt'), $this->iterate($task, $state, $this->tmpDir.'/a.txt')],
        );
    }

    public function testInputLineReaderReadsDifferentFilesSuccessively(): void
    {
        $task = new InputLineReaderTask();
        $state = $this->createState([]);

        self::assertSame(["a1\n", "a2\n"], $this->iterate($task, $state, $this->tmpDir.'/a.txt'));
        self::assertSame(["b1\n"], $this->iterate($task, $state, $this->tmpDir.'/b.txt'));
    }

    /**
     * Mimics the ProcessManager loop over an iterable task and returns the non-skipped outputs.
     *
     * @return list<mixed>
     */
    private function iterate(LineReaderTask $task, ProcessState $state, mixed $input = null): array
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

    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('read', LineReaderTask::class, $options));

        return $state;
    }
}
