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
use CleverAge\ProcessBundle\Task\File\JsonStream\JsonStreamWriterTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(JsonStreamWriterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(JsonStreamFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class JsonStreamWriterTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().'/'.uniqid('json_stream_writer_test_', true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testWritesInMissingParentDirectory(): void
    {
        $filePath = $this->tmpDir.'/sub/out.jsonl';

        $state = $this->write(['file_path' => $filePath], [['a' => 1], ['b' => 2]]);

        self::assertSame($filePath, $state->getOutput());
        self::assertSame('{"a":1}'.\PHP_EOL.'{"b":2}'.\PHP_EOL, file_get_contents($filePath));
    }

    public function testExistingFileIsOverwrittenByDefault(): void
    {
        $filePath = $this->tmpDir.'/out.jsonl';
        (new Filesystem())->dumpFile($filePath, '{"old":true}'.\PHP_EOL);

        $this->write(['file_path' => $filePath], [['a' => 1]]);

        self::assertSame('{"a":1}'.\PHP_EOL, file_get_contents($filePath));
    }

    public function testModeIsUsedToOpenTheFile(): void
    {
        $filePath = $this->tmpDir.'/out.jsonl';
        (new Filesystem())->dumpFile($filePath, '{"old":true}'.\PHP_EOL);

        $this->write(['file_path' => $filePath, 'mode' => 'ab'], [['a' => 1]]);

        self::assertSame('{"old":true}'.\PHP_EOL.'{"a":1}'.\PHP_EOL, file_get_contents($filePath));
    }

    public function testModeMustBeAString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->write(['file_path' => $this->tmpDir.'/out.jsonl', 'mode' => true], []);
    }

    public function testPlaceholdersAreReplacedInTheWrittenAndOutputPath(): void
    {
        $state = $this->write(['file_path' => $this->tmpDir.'/out_{date}_{unique_token}.jsonl'], [['a' => 1], ['b' => 2]]);

        $output = $state->getOutput();
        self::assertIsString($output);
        self::assertStringStartsWith($this->tmpDir.'/out_'.date('Ymd').'_', $output);
        self::assertStringNotContainsString('{', $output);
        self::assertSame('{"a":1}'.\PHP_EOL.'{"b":2}'.\PHP_EOL, file_get_contents($output));
    }

    public function testNonArrayInputThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Input value is not an array');

        $this->write(['file_path' => $this->tmpDir.'/out.jsonl'], ['not an array']);
    }

    public function testFinalizeReleasesTheFile(): void
    {
        $filePath = $this->tmpDir.'/out.jsonl';
        $task = new JsonStreamWriterTask();
        $state = $this->createState(['file_path' => $filePath]);
        $task->initialize($state);
        $state->setInput(['a' => 1]);
        $task->execute($state);

        $task->finalize($state);
        $state->setInput(['b' => 2]);
        $task->execute($state);

        // The file is opened again in "wb" mode on the following execution
        self::assertSame('{"b":2}'.\PHP_EOL, file_get_contents($filePath));
    }

    /**
     * @param array<string, mixed> $options
     * @param list<mixed>          $inputs
     */
    private function write(array $options, array $inputs): ProcessState
    {
        $task = new JsonStreamWriterTask();
        $state = $this->createState($options);
        $task->initialize($state);
        foreach ($inputs as $input) {
            $state->setInput($input);
            $task->execute($state);
        }
        $task->proceed($state);

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
        $state->setTaskConfiguration(new TaskConfiguration('write', JsonStreamWriterTask::class, $options));

        return $state;
    }
}
