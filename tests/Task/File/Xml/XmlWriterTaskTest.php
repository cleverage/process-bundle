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
use CleverAge\ProcessBundle\Task\File\Xml\XmlWriterTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(XmlWriterTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(XmlFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class XmlWriterTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('xml_writer_test_', true);
        (new Filesystem())->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testWritesTheDocumentAndOutputsTheFilePath(): void
    {
        $filePath = $this->tmpDir.'/file.xml';
        $task = new XmlWriterTask(new NullLogger());
        $state = $this->createState(['file_path' => $filePath]);
        $state->setInput($this->createDocument('<root><a>ok</a></root>'));
        $task->initialize($state);

        $task->execute($state);

        self::assertSame($filePath, $state->getOutput());
        self::assertSame("<?xml version=\"1.0\"?>\n<root><a>ok</a></root>\n", file_get_contents($filePath));
    }

    public function testEachExecutionOverwritesTheFileWithDefaultMode(): void
    {
        $filePath = $this->tmpDir.'/file.xml';
        $task = new XmlWriterTask(new NullLogger());
        $state = $this->createState(['file_path' => $filePath]);
        $task->initialize($state);

        $state->setInput($this->createDocument('<first/>'));
        $task->execute($state);
        $state->setInput($this->createDocument('<second/>'));
        $task->execute($state);

        self::assertSame("<?xml version=\"1.0\"?>\n<second/>\n", file_get_contents($filePath));
    }

    public function testAppendModeKeepsPreviousContent(): void
    {
        $filePath = $this->tmpDir.'/file.xml';
        $task = new XmlWriterTask(new NullLogger());
        $state = $this->createState(['file_path' => $filePath, 'mode' => 'ab']);
        $task->initialize($state);

        $state->setInput($this->createDocument('<first/>'));
        $task->execute($state);
        $state->setInput($this->createDocument('<second/>'));
        $task->execute($state);

        self::assertSame(
            "<?xml version=\"1.0\"?>\n<first/>\n<?xml version=\"1.0\"?>\n<second/>\n",
            file_get_contents($filePath),
        );
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidInputProvider(): iterable
    {
        yield 'null' => [null];
        yield 'string' => ['<root/>'];
        yield 'array' => [['root' => 'foo']];
        yield 'dom element' => [new \DOMElement('root')];
    }

    #[DataProvider('invalidInputProvider')]
    public function testNonDomDocumentInputThrows(mixed $input): void
    {
        $filePath = $this->tmpDir.'/file.xml';
        $task = new XmlWriterTask(new NullLogger());
        $state = $this->createState(['file_path' => $filePath]);
        $state->setInput($input);
        $task->initialize($state);

        try {
            $task->execute($state);
            self::fail('An exception should have been thrown');
        } catch (\UnexpectedValueException $e) {
            self::assertSame('Input must be a \DOMDocument', $e->getMessage());
        }
        self::assertFileDoesNotExist($filePath);
    }

    public function testFilePathIsRequired(): void
    {
        $task = new XmlWriterTask(new NullLogger());

        $this->expectException(MissingOptionsException::class);

        $task->initialize($this->createState([]));
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'file_path not a string' => [['file_path' => 42]];
        yield 'mode not a string' => [['file_path' => '/tmp/file.xml', 'mode' => 1]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testInvalidOptionsThrow(array $options): void
    {
        $task = new XmlWriterTask(new NullLogger());

        $this->expectException(InvalidOptionsException::class);

        $task->initialize($this->createState($options));
    }

    private function createDocument(string $xml): \DOMDocument
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        return $dom;
    }

    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('write', XmlWriterTask::class, $options));

        return $state;
    }
}
