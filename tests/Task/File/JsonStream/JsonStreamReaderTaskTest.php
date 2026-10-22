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

namespace CleverAge\ProcessBundle\Tests\Task\File\JsonStream;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Filesystem\JsonStreamFile;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamReaderTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(JsonStreamReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(JsonStreamFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class JsonStreamReaderTaskTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('json_stream_reader_test_', true);
        $this->filesystem = new Filesystem();
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testOutputsEachDecodedLine(): void
    {
        $file = $this->createFile("{\"id\":1,\"name\":\"foo\"}\n[1,2]\n{\"nested\":{\"a\":true}}\n");
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        self::assertSame(
            [['id' => 1, 'name' => 'foo'], [1, 2], ['nested' => ['a' => true]]],
            $this->iterate($task, $state, $file),
        );
    }

    public function testReadsLastLineWithoutTrailingNewline(): void
    {
        $file = $this->createFile("{\"a\":1}\n{\"b\":2}");
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        self::assertSame([['a' => 1], ['b' => 2]], $this->iterate($task, $state, $file));
    }

    public function testSkipsEmptyAndNullLines(): void
    {
        $file = $this->createFile("{\"a\":1}\n\nnull\n{\"b\":2}\n\n");
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        self::assertSame([['a' => 1], ['b' => 2]], $this->iterate($task, $state, $file));
    }

    public function testSkipsEmptyFile(): void
    {
        $file = $this->createFile('');
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        self::assertSame([], $this->iterate($task, $state, $file));
    }

    public function testReadsSameFileAgainOnSecondExecution(): void
    {
        $file = $this->createFile("{\"a\":1}\n{\"b\":2}\n");
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        $expected = [['a' => 1], ['b' => 2]];
        self::assertSame(
            [$expected, $expected],
            [$this->iterate($task, $state, $file), $this->iterate($task, $state, $file)],
        );
    }

    public function testReadsDifferentFilesSuccessively(): void
    {
        $fileA = $this->createFile("{\"a\":1}\n{\"a\":2}\n", 'a.jsonl');
        $fileB = $this->createFile("{\"b\":1}\n", 'b.jsonl');
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        self::assertSame([['a' => 1], ['a' => 2]], $this->iterate($task, $state, $fileA));
        self::assertSame([['b' => 1]], $this->iterate($task, $state, $fileB));
    }

    public function testThrowsOnInvalidJsonByDefault(): void
    {
        $file = $this->createFile("{\"a\":1}\n{invalid\n");
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        $this->expectException(\JsonException::class);

        $this->iterate($task, $state, $file);
    }

    public function testSkipsInvalidJsonWithoutJsonFlags(): void
    {
        $file = $this->createFile("{\"a\":1}\n{invalid\n{\"b\":2}\n");
        $task = new JsonStreamReaderTask();
        $state = $this->createState(['json_flags' => []]);

        self::assertSame([['a' => 1], ['b' => 2]], $this->iterate($task, $state, $file));
    }

    public function testAppliesJsonFlags(): void
    {
        $file = $this->createFile("{\"big\":123456789012345678901234567890}\n");
        $task = new JsonStreamReaderTask();
        $state = $this->createState(['json_flags' => [\JSON_THROW_ON_ERROR, \JSON_BIGINT_AS_STRING]]);

        self::assertSame([['big' => '123456789012345678901234567890']], $this->iterate($task, $state, $file));
    }

    public function testAppliesSplFileObjectFlags(): void
    {
        // Without any flag, the blank line is read as "\n" and fails to decode with the default JSON_THROW_ON_ERROR flag
        $file = $this->createFile("{\"a\":1}\n\n{\"b\":2}\n");
        $task = new JsonStreamReaderTask();
        $state = $this->createState(['spl_file_object_flags' => []]);

        $this->expectException(\JsonException::class);

        $this->iterate($task, $state, $file);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function scalarLineProvider(): iterable
    {
        yield 'integer' => ['42', 'int'];
        yield 'string' => ['"foo"', 'string'];
        yield 'boolean' => ['true', 'bool'];
    }

    #[DataProvider('scalarLineProvider')]
    public function testThrowsOnScalarLine(string $line, string $type): void
    {
        $file = $this->createFile("{\"a\":1}\n{$line}\n");
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(\sprintf('Line 2 of file "%s" must be a JSON object or array, got %s', $file, $type));

        $this->iterate($task, $state, $file);
    }

    public function testThrowsWhenFileDoesNotExist(): void
    {
        $task = new JsonStreamReaderTask();
        $state = $this->createState([]);

        $this->expectException(\RuntimeException::class);

        $this->iterate($task, $state, $this->tmpDir.'/missing.jsonl');
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'spl_file_object_flags not an array' => [['spl_file_object_flags' => \SplFileObject::SKIP_EMPTY]];
        yield 'json_flags not an array' => [['json_flags' => \JSON_THROW_ON_ERROR]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testThrowsOnInvalidOptions(array $options): void
    {
        $task = new JsonStreamReaderTask();
        $state = $this->createState($options);

        $this->expectException(InvalidOptionsException::class);

        $task->initialize($state);
    }

    public function testNextWithoutFileReturnsFalse(): void
    {
        self::assertFalse((new JsonStreamReaderTask())->next($this->createState([])));
    }

    /**
     * Mimics the ProcessManager loop over an iterable task and returns the non-skipped outputs.
     *
     * @return list<mixed>
     */
    private function iterate(JsonStreamReaderTask $task, ProcessState $state, string $input): array
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

    private function createFile(string $content, string $name = 'data.jsonl'): string
    {
        $path = $this->tmpDir.'/'.$name;
        $this->filesystem->dumpFile($path, $content);

        return $path;
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
        $state->setTaskConfiguration(new TaskConfiguration('read', JsonStreamReaderTask::class, $options));

        return $state;
    }
}
