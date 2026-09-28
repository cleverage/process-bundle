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
        @unlink($this->tmpDir.'/sub/out.jsonl');
        @rmdir($this->tmpDir.'/sub');
        @rmdir($this->tmpDir);
    }

    public function testWritesInMissingParentDirectory(): void
    {
        $filePath = $this->tmpDir.'/sub/out.jsonl';

        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('write', JsonStreamWriterTask::class, ['file_path' => $filePath]));

        $task = new JsonStreamWriterTask();
        $task->initialize($state);
        foreach ([['a' => 1], ['b' => 2]] as $input) {
            $state->setInput($input);
            $task->execute($state);
        }
        $task->proceed($state);

        self::assertSame($filePath, $state->getOutput());
        self::assertSame('{"a":1}'.\PHP_EOL.'{"b":2}'.\PHP_EOL, file_get_contents($filePath));
    }
}
