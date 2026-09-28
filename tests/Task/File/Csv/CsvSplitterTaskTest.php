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
use CleverAge\ProcessBundle\Task\File\Csv\CsvSplitterTask;
use CleverAge\ProcessBundle\Task\File\Csv\InputCsvReaderTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(CsvSplitterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CsvReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(InputCsvReaderTask::class)]
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
class CsvSplitterTaskTest extends TestCase
{
    private string $tmpDir;

    /** @var list<string> */
    private array $producedFiles = [];

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('csv_splitter_test_', true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove([$this->tmpDir, ...$this->producedFiles]);
    }

    /**
     * @return iterable<string, array{int, int, list<int>}>
     */
    public static function splitProvider(): iterable
    {
        yield 'last file shorter' => [10, 4, [4, 4, 2]];
        yield 'exact multiple of max_lines' => [10, 5, [5, 5]];
        yield 'one line per file' => [3, 1, [1, 1, 1]];
        yield 'max_lines greater than the line count' => [3, 1000, [3]];
    }

    /**
     * @param list<int> $expectedLineCounts
     */
    #[DataProvider('splitProvider')]
    public function testEachFileContainsMaxLinesDataLines(int $lineCount, int $maxLines, array $expectedLineCounts): void
    {
        $source = $this->createSource($lineCount);

        $files = $this->split($source, $maxLines);

        self::assertSame($expectedLineCounts, array_map(static fn (array $lines): int => \count($lines) - 1, $files));
        // Every file starts with the headers, and every data line is kept once, in order
        $dataLines = [];
        foreach ($files as $lines) {
            self::assertSame("id;name\n", $lines[0]);
            array_push($dataLines, ...\array_slice($lines, 1));
        }
        self::assertSame(\array_slice(file($source) ?: [], 1), $dataLines);
    }

    public function testNoFileForASourceWithoutDataLine(): void
    {
        self::assertSame([], $this->split($this->createSource(0), 5));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidMaxLinesProvider(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
        yield 'string' => ['5'];
    }

    #[DataProvider('invalidMaxLinesProvider')]
    public function testInvalidMaxLinesIsRejected(mixed $maxLines): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->createTask($maxLines);
    }

    private function createSource(int $lineCount): string
    {
        $content = "id;name\n";
        for ($i = 1; $i <= $lineCount; ++$i) {
            $content .= "{$i};name{$i}\n";
        }
        $path = $this->tmpDir.'/source.csv';
        (new Filesystem())->dumpFile($path, $content);

        return $path;
    }

    /**
     * Iterate over the task like the ProcessManager does, returning the lines of each produced file.
     *
     * @return list<list<string>>
     */
    private function split(string $source, int $maxLines): array
    {
        [$task, $state] = $this->createTask($maxLines);
        $files = [];
        $iterations = 0;
        do {
            $state->reset(false);
            $state->setInput($source);
            $task->execute($state);
            if (!$state->isSkipped()) {
                $this->producedFiles[] = $state->getOutput();
                $files[] = file($state->getOutput()) ?: [];
            }
        } while ($task->next($state) && ++$iterations < 100);

        return $files;
    }

    /**
     * @return array{CsvSplitterTask, ProcessState}
     */
    private function createTask(mixed $maxLines): array
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('split', CsvSplitterTask::class, ['max_lines' => $maxLines]));
        $task = new CsvSplitterTask(new NullLogger());
        $task->initialize($state);

        return [$task, $state];
    }
}
