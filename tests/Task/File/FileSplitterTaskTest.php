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
use CleverAge\ProcessBundle\Filesystem\SplFile;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\FileSplitterTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(FileSplitterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(SplFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class FileSplitterTaskTest extends TestCase
{
    /** @var list<string> */
    private array $tmpFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $tmpFile) {
            if (is_file($tmpFile)) {
                unlink($tmpFile);
            }
        }
        $this->tmpFiles = [];
    }

    /**
     * @return iterable<string, array{string, int, list<string>}>
     */
    public static function provideSplitCases(): iterable
    {
        yield 'last chunk is shorter' => ["a\nb\nc\nd\ne\n", 2, ["a\nb\n", "c\nd\n", "e\n"]];
        yield 'exact multiple of max_lines' => ["a\nb\nc\nd\n", 2, ["a\nb\n", "c\nd\n"]];
        yield 'no trailing line break' => ["a\nb\nc", 2, ["a\nb\n", "c\n"]];
        yield 'one line per file' => ["a\nb\nc\n", 1, ["a\n", "b\n", "c\n"]];
        yield 'max_lines greater than line count' => ["a\nb\nc\n", 10, ["a\nb\nc\n"]];
        yield 'empty lines are kept' => ["a\n\nb\n\n", 2, ["a\n\n", "b\n\n"]];
        yield 'CRLF line breaks are not doubled' => ["a\r\nb\r\nc\r\n", 2, ["a\nb\n", "c\n"]];
        yield 'line content is preserved' => ["  a;b  \n\tc\r\n", 5, ["  a;b  \n\tc\n"]];
    }

    /**
     * @param list<string> $expectedChunks
     */
    #[DataProvider('provideSplitCases')]
    public function testSplit(string $content, int $maxLines, array $expectedChunks): void
    {
        $filePath = $this->createSourceFile($content);

        $chunks = $this->runTask(new FileSplitterTask(), ['file_path' => $filePath, 'max_lines' => $maxLines]);

        $this->assertSame(
            array_map(static fn (string $chunk): string => str_replace("\n", \PHP_EOL, $chunk), $expectedChunks),
            $chunks,
        );
    }

    public function testEmptyFileProducesNoOutput(): void
    {
        $filePath = $this->createSourceFile('');
        $task = new FileSplitterTask();
        $state = $this->createState(['file_path' => $filePath, 'max_lines' => 2]);

        $task->execute($state);

        $this->assertTrue($state->isSkipped());
        $this->assertNull($state->getOutput());
        $this->assertFalse($task->next($state));
    }

    public function testFilePathAndMaxLinesCanBeGivenAsInput(): void
    {
        $filePath = $this->createSourceFile("a\nb\nc\n");

        $chunks = $this->runTask(
            new FileSplitterTask(),
            ['file_path' => '/does/not/exist', 'max_lines' => 10],
            ['file_path' => $filePath, 'max_lines' => 2],
        );

        $this->assertSame(['a'.\PHP_EOL.'b'.\PHP_EOL, 'c'.\PHP_EOL], $chunks);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, mixed}>
     */
    public static function provideInvalidMaxLines(): iterable
    {
        yield 'zero as option' => [['max_lines' => 0], null];
        yield 'negative as option' => [['max_lines' => -1], null];
        yield 'zero as input' => [[], ['max_lines' => 0]];
        yield 'string as input' => [[], ['max_lines' => 'abc']];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideInvalidMaxLines')]
    public function testInvalidMaxLinesIsRejected(array $options, mixed $input): void
    {
        $filePath = $this->createSourceFile("a\nb\n");

        // max_lines lower than 1 used to loop forever, a string one to throw a TypeError
        $this->expectException(InvalidOptionsException::class);

        $this->runTask(new FileSplitterTask(), ['file_path' => $filePath, ...$options], $input);
    }

    public function testTaskCanBeReusedAfterIteration(): void
    {
        $filePath = $this->createSourceFile("a\nb\nc\n");
        $task = new FileSplitterTask();
        $options = ['file_path' => $filePath, 'max_lines' => 2];
        $expected = ['a'.\PHP_EOL.'b'.\PHP_EOL, 'c'.\PHP_EOL];

        $firstRun = $this->runTask($task, $options);
        $secondRun = $this->runTask($task, $options);

        $this->assertSame($expected, $firstRun);
        $this->assertSame($firstRun, $secondRun);
    }

    /**
     * Mimics the process manager loop on an iterable task: execute(), then next() until it returns false.
     *
     * @param array<string, mixed> $options
     *
     * @return list<string> content of each produced file
     */
    private function runTask(FileSplitterTask $task, array $options, mixed $input = null): array
    {
        $chunks = [];
        $iterations = 0;
        do {
            $state = $this->createState($options);
            $state->setInput($input);
            $task->execute($state);
            if (!$state->isSkipped()) {
                $outputFile = $state->getOutput();
                $this->assertIsString($outputFile);
                $this->tmpFiles[] = $outputFile;
                $chunks[] = (string) file_get_contents($outputFile);
            }
            $this->assertLessThan(100, ++$iterations, 'Infinite iteration');
        } while ($task->next($state));

        return $chunks;
    }

    private function createSourceFile(string $content): string
    {
        $filePath = (string) tempnam(sys_get_temp_dir(), 'file_splitter_test_');
        file_put_contents($filePath, $content);
        $this->tmpFiles[] = $filePath;

        return $filePath;
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
        $state->setSkipped(false);
        $state->setTaskConfiguration(new TaskConfiguration('split', FileSplitterTask::class, $options));

        return $state;
    }
}
