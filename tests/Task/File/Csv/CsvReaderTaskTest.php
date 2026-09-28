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

namespace CleverAge\ProcessBundle\Tests\Task\File\Csv;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Filesystem\CsvFile;
use CleverAge\ProcessBundle\Filesystem\CsvResource;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\Csv\AbstractCsvResourceTask;
use CleverAge\ProcessBundle\Task\File\Csv\AbstractCsvTask;
use CleverAge\ProcessBundle\Task\File\Csv\CsvReaderTask;
use CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

#[\PHPUnit\Framework\Attributes\CoversClass(CsvReaderTask::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(InputCsvReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractCsvTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractCsvResourceTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CsvFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CsvResource::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class CsvReaderTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('csv_reader_test_', true);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->tmpDir.'/a.csv', "id;name\n1;foo\n2;bar\n");
        $filesystem->dumpFile($this->tmpDir.'/b.csv', "id;name\n3;baz\n");
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testSameFileIsReadAgainOnSecondExecution(): void
    {
        $task = new CsvReaderTask(new NullLogger());
        $state = $this->createState(['file_path' => $this->tmpDir.'/a.csv']);

        $expected = [['id' => '1', 'name' => 'foo'], ['id' => '2', 'name' => 'bar']];
        self::assertSame(
            [$expected, $expected],
            [$this->iterate($task, $state), $this->iterate($task, $state)],
        );
        $task->finalize($state);
    }

    public function testInputCsvReaderReadsSameFileTwice(): void
    {
        $task = new InputCsvReaderTask(new NullLogger());
        $state = $this->createState([]);

        $expected = [['id' => '1', 'name' => 'foo'], ['id' => '2', 'name' => 'bar']];
        self::assertSame(
            [$expected, $expected],
            [$this->iterate($task, $state, $this->tmpDir.'/a.csv'), $this->iterate($task, $state, $this->tmpDir.'/a.csv')],
        );
        $task->finalize($state);
    }

    public function testInputCsvReaderReadsDifferentFilesSuccessively(): void
    {
        $task = new InputCsvReaderTask(new NullLogger());
        $state = $this->createState([]);

        self::assertSame(
            [['id' => '1', 'name' => 'foo'], ['id' => '2', 'name' => 'bar']],
            $this->iterate($task, $state, $this->tmpDir.'/a.csv'),
        );
        self::assertSame([['id' => '3', 'name' => 'baz']], $this->iterate($task, $state, $this->tmpDir.'/b.csv'));
        $task->finalize($state);
    }

    /**
     * Mimics the ProcessManager loop over an iterable task and returns the non-skipped outputs.
     *
     * @return list<mixed>
     */
    private function iterate(CsvReaderTask $task, ProcessState $state, mixed $input = null): array
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
        $state->setTaskConfiguration(new TaskConfiguration('read', CsvReaderTask::class, $options));

        return $state;
    }
}
