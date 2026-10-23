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
use CleverAge\ProcessBundle\Exception\RuntimeException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\Yaml\YamlWriterTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Yaml\Yaml;

#[\PHPUnit\Framework\Attributes\CoversClass(YamlWriterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class YamlWriterTaskTest extends TestCase
{
    private const DATA = [
        'foo' => [
            'name' => 'Foo',
            'tags' => ['a', 'b'],
        ],
    ];

    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('yaml_writer_test_', true);
        (new Filesystem())->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testWritesTheInputAndOutputsTheFilePath(): void
    {
        $filePath = $this->tmpDir.'/file.yaml';
        $state = $this->execute(['file_path' => $filePath], self::DATA);

        self::assertSame($filePath, $state->getOutput());
        self::assertSame("foo:\n    name: Foo\n    tags:\n        - a\n        - b\n", file_get_contents($filePath));
        self::assertSame(self::DATA, Yaml::parseFile($filePath));
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function inlineProvider(): iterable
    {
        yield 'inline at root' => [0, '{ foo: { name: Foo, tags: [a, b] } }'];
        yield 'inline at first level' => [1, "foo: { name: Foo, tags: [a, b] }\n"];
        yield 'inline at second level' => [2, "foo:\n    name: Foo\n    tags: [a, b]\n"];
    }

    #[DataProvider('inlineProvider')]
    public function testInlineOptionIsUsed(int $inline, string $expected): void
    {
        $filePath = $this->tmpDir.'/file.yaml';
        $this->execute(['file_path' => $filePath, 'inline' => $inline], self::DATA);

        self::assertSame($expected, file_get_contents($filePath));
    }

    public function testEachExecutionOverwritesTheFile(): void
    {
        $filePath = $this->tmpDir.'/file.yaml';
        $task = new YamlWriterTask();
        $state = $this->createState(['file_path' => $filePath]);
        $task->initialize($state);

        $state->setInput(['first' => 1]);
        $task->execute($state);
        $state->setInput(['second' => 2]);
        $task->execute($state);

        self::assertSame("second: 2\n", file_get_contents($filePath));
    }

    public function testTheParentDirectoryIsCreated(): void
    {
        $filePath = $this->tmpDir.'/sub/dir/file.yaml';
        $this->execute(['file_path' => $filePath], self::DATA);

        self::assertSame(self::DATA, Yaml::parseFile($filePath));
    }

    public function testTheParentDirectoryOfAFileUrlIsCreated(): void
    {
        $filePath = $this->tmpDir.'/url/file.yaml';
        $this->execute(['file_path' => 'file://'.$filePath], self::DATA);

        self::assertSame(self::DATA, Yaml::parseFile($filePath));
    }

    public function testAStreamWrapperPathIsWritten(): void
    {
        $filePath = 'compress.zlib://'.$this->tmpDir.'/file.yaml.gz';
        $state = $this->execute(['file_path' => $filePath], self::DATA);

        self::assertSame($filePath, $state->getOutput());
        self::assertSame(self::DATA, Yaml::parse((string) file_get_contents($filePath)));
        self::assertSame("\x1f\x8b", substr((string) file_get_contents($this->tmpDir.'/file.yaml.gz'), 0, 2));
    }

    public function testAStreamWrapperPathDoesNotCreateADirectory(): void
    {
        $cwd = getcwd();
        self::assertNotFalse($cwd);
        chdir($this->tmpDir);
        try {
            $this->execute(['file_path' => 'php://memory'], self::DATA);
            ob_start();
            try {
                $this->execute(['file_path' => 'php://output'], self::DATA);
            } finally {
                $output = ob_get_clean();
            }
        } finally {
            chdir($cwd);
        }

        self::assertSame(self::DATA, Yaml::parse((string) $output));

        self::assertSame([], glob($this->tmpDir.'/*'));
    }

    public function testAWriteFailureThrows(): void
    {
        // A directory cannot be written as a file
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('Unable to write file "%s": ', $this->tmpDir));

        $this->execute(['file_path' => $this->tmpDir], self::DATA);
    }

    public function testScalarInputIsDumped(): void
    {
        $filePath = $this->tmpDir.'/file.yaml';
        $this->execute(['file_path' => $filePath], 'foo');

        self::assertSame('foo', file_get_contents($filePath));
    }

    public function testFilePathIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        (new YamlWriterTask())->initialize($this->createState([]));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'file_path not a string' => [['file_path' => 42]];
        yield 'inline not an int' => [['file_path' => '/tmp/file.yaml', 'inline' => '2']];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrow(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        (new YamlWriterTask())->initialize($this->createState($options));
    }

    /**
     * @param array<string, mixed> $options
     */
    private function execute(array $options, mixed $input): ProcessState
    {
        $task = new YamlWriterTask();
        $state = $this->createState($options);
        $state->setInput($input);
        $task->initialize($state);
        $task->execute($state);

        return $state;
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
        $state->setTaskConfiguration(new TaskConfiguration('write', YamlWriterTask::class, $options));

        return $state;
    }
}
