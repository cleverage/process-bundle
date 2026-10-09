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

namespace CleverAge\ProcessBundle\Tests\Task\File\Xml;

use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
use CleverAge\ProcessBundle\Configuration\TaskConfiguration;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Filesystem\XmlFile;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\Xml\XmlReaderTask;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(XmlReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(XmlFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class XmlReaderTaskTest extends TestCase
{
    private string $tmpDir;

    /** @var list<array{mixed, string}> */
    private array $logs = [];

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('xml_reader_test_', true);
        (new Filesystem())->dumpFile($this->tmpDir.'/file.xml', '<root><item id="1">foo</item><item id="2">bar</item></root>');
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testOutputsDomDocumentOfTheFile(): void
    {
        $state = $this->execute(['file_path' => $this->tmpDir.'/file.xml']);

        $output = $state->getOutput();
        self::assertInstanceOf(\DOMDocument::class, $output);
        self::assertSame('root', $output->documentElement?->nodeName);
        self::assertSame(2, $output->getElementsByTagName('item')->length);
        self::assertSame('bar', $output->getElementsByTagName('item')->item(1)?->textContent);
        self::assertSame([], $this->logs);
    }

    public function testInputIsIgnoredWithAWarning(): void
    {
        $state = $this->execute(['file_path' => $this->tmpDir.'/file.xml'], 'ignored input');

        self::assertInstanceOf(\DOMDocument::class, $state->getOutput());
        self::assertSame([[LogLevel::WARNING, 'Input has been ignored for XMLReaderTask']], $this->logs);
    }

    public function testCustomModeIsUsed(): void
    {
        $state = $this->execute(['file_path' => $this->tmpDir.'/file.xml', 'mode' => 'r']);

        self::assertInstanceOf(\DOMDocument::class, $state->getOutput());
    }

    public function testFileIsReadAgainOnEachExecution(): void
    {
        $task = $this->createTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/file.xml']);
        $task->initialize($state);

        $task->execute($state);
        $first = $state->getOutput();
        file_put_contents($this->tmpDir.'/file.xml', '<other/>');
        $task->execute($state);
        $second = $state->getOutput();

        self::assertInstanceOf(\DOMDocument::class, $first);
        self::assertInstanceOf(\DOMDocument::class, $second);
        self::assertNotSame($first, $second);
        self::assertSame('other', $second->documentElement?->nodeName);
    }

    public function testEmptyFileThrows(): void
    {
        file_put_contents($this->tmpDir.'/empty.xml', '');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('is empty');

        $this->execute(['file_path' => $this->tmpDir.'/empty.xml']);
    }

    public function testInvalidXmlThrows(): void
    {
        file_put_contents($this->tmpDir.'/invalid.xml', '<root><a>1</a>');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid XML in file');

        $this->execute(['file_path' => $this->tmpDir.'/invalid.xml']);
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->execute(['file_path' => $this->tmpDir.'/missing.xml']);
    }

    public function testFilePathIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->execute([]);
    }

    public function testFilePathMustBeAString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->execute(['file_path' => 42]);
    }

    public function testModeMustBeAString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->execute(['file_path' => $this->tmpDir.'/file.xml', 'mode' => true]);
    }

    private function execute(array $options, mixed $input = null): ProcessState
    {
        $task = $this->createTask();
        $state = $this->createState($options);
        $state->setInput($input);
        $task->initialize($state);
        $task->execute($state);

        return $state;
    }

    private function createTask(): XmlReaderTask
    {
        return new XmlReaderTask(new class(function (mixed $level, string $message): void {
            $this->logs[] = [$level, $message];
        }) extends AbstractLogger {
            public function __construct(private readonly \Closure $onLog)
            {
            }

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                ($this->onLog)($level, (string) $message);
            }
        });
    }

    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('read', XmlReaderTask::class, $options));

        return $state;
    }
}
