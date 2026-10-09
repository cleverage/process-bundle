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

namespace CleverAge\ProcessBundle\Tests\Task\File\Yaml;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\AbstractIterableOutputTask;
use CleverAge\ProcessBundle\Task\File\Yaml\YamlReaderTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Yaml\Exception\ParseException;

#[\PHPUnit\Framework\Attributes\CoversClass(YamlReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractIterableOutputTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class YamlReaderTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('yaml_reader_test_', true);
        (new Filesystem())->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testIteratesOverRootMappingValues(): void
    {
        $filePath = $this->dump("foo:\n  name: Foo\n  tags: [a, b]\nbar:\n  name: Bar\n");
        $task = new YamlReaderTask();
        $state = $this->createState(['file_path' => $filePath]);
        $task->initialize($state);

        [$outputs, $keys] = $this->iterate($task, $state);

        self::assertSame([['name' => 'Foo', 'tags' => ['a', 'b']], ['name' => 'Bar']], $outputs);
        self::assertSame(['foo', 'bar'], $keys);
        self::assertSame([], $state->getErrorContext());
    }

    public function testIteratesOverRootSequence(): void
    {
        $filePath = $this->dump("- 1\n- two\n- { three: 3 }\n");
        $task = new YamlReaderTask();
        $state = $this->createState(['file_path' => $filePath]);
        $task->initialize($state);

        [$outputs, $keys] = $this->iterate($task, $state);

        self::assertSame([1, 'two', ['three' => 3]], $outputs);
        self::assertSame([0, 1, 2], $keys);
    }

    public function testInputIsIgnoredAndFileIsReadAgainOnNextExecution(): void
    {
        $filePath = $this->dump("- a\n- b\n");
        $task = new YamlReaderTask();
        $state = $this->createState(['file_path' => $filePath]);
        $task->initialize($state);

        self::assertSame([['a', 'b'], [0, 1]], $this->iterate($task, $state, 'ignored'));
        self::assertSame([['a', 'b'], [0, 1]], $this->iterate($task, $state, ['ignored']));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function emptyRootProvider(): iterable
    {
        yield 'empty mapping' => ['{}'];
        yield 'empty sequence' => ['[]'];
    }

    #[DataProvider('emptyRootProvider')]
    public function testEmptyRootIsSkipped(string $content): void
    {
        $task = new YamlReaderTask();
        $state = $this->createState(['file_path' => $this->dump($content)]);
        $task->initialize($state);

        $task->execute($state);

        self::assertTrue($state->isSkipped());
        self::assertFalse($task->next($state));
        self::assertSame([], $state->getErrorContext());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function notArrayRootProvider(): iterable
    {
        yield 'empty file' => [''];
        yield 'scalar string' => ['foo'];
        yield 'scalar int' => ['42'];
        yield 'null' => ['~'];
    }

    #[DataProvider('notArrayRootProvider')]
    public function testNotArrayRootThrows(string $content): void
    {
        $filePath = $this->dump($content);
        $task = new YamlReaderTask();
        $state = $this->createState(['file_path' => $filePath]);
        $task->initialize($state);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("File content is not an array: {$filePath}");

        $task->execute($state);
    }

    public function testInvalidYamlThrows(): void
    {
        $task = new YamlReaderTask();
        $state = $this->createState(['file_path' => $this->dump("foo: [a, b\n")]);
        $task->initialize($state);

        $this->expectException(ParseException::class);

        $task->execute($state);
    }

    public function testMissingFileThrowsAtInitialization(): void
    {
        $filePath = $this->tmpDir.'/missing.yaml';
        $task = new YamlReaderTask();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("File not found: {$filePath}");

        $task->initialize($this->createState(['file_path' => $filePath]));
    }

    public function testFilePathIsRequired(): void
    {
        $task = new YamlReaderTask();

        $this->expectException(MissingOptionsException::class);

        $task->initialize($this->createState([]));
    }

    public function testFilePathMustBeAString(): void
    {
        $task = new YamlReaderTask();

        $this->expectException(InvalidOptionsException::class);

        $task->initialize($this->createState(['file_path' => ['foo.yaml']]));
    }

    private function dump(string $content): string
    {
        $filePath = $this->tmpDir.\DIRECTORY_SEPARATOR.uniqid('file_', true).'.yaml';
        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * Mimics the ProcessManager loop over an iterable task and returns the non-skipped outputs and their keys.
     *
     * @return array{list<mixed>, list<mixed>}
     */
    private function iterate(YamlReaderTask $task, ProcessState $state, mixed $input = null): array
    {
        $outputs = [];
        $keys = [];
        $state->setInput($input);
        do {
            $state->setSkipped(false);
            $task->execute($state);
            if (!$state->isSkipped()) {
                $outputs[] = $state->getOutput();
                $keys[] = $state->getErrorContext()['iterator_key'] ?? null;
            }
        } while ($task->next($state));

        return [$outputs, $keys];
    }

    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('read', YamlReaderTask::class, $options));

        return $state;
    }
}
