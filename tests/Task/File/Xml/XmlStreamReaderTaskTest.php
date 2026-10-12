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
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\Xml\XmlStreamReaderTask;
use CleverAge\ProcessBundle\Transformer\Xml\XpathEvaluatorTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(XmlStreamReaderTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(XpathEvaluatorTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class XmlStreamReaderTaskTest extends TestCase
{
    private const CATALOG = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <catalog xmlns:g="http://example.com/g">
            <product id="1">
                <name>Foo</name>
                <g:price>10</g:price>
                <variants><product id="1-a"/></variants>
            </product>
            <category>ignored</category>
            <product id="2"><name>Bar</name><g:price>20</g:price></product>
        </catalog>
        XML;

    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('xml_stream_reader_test_', true);
        (new Filesystem())->dumpFile($this->tmpDir.'/catalog.xml', self::CATALOG);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testOutputsEachMatchingElement(): void
    {
        $outputs = $this->iterate(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 'product']);

        // The nested product is part of the first product and is not iterated
        self::assertCount(2, $outputs);
        self::assertSame(['1', '2'], array_map(static fn (\DOMElement $element): string => $element->getAttribute('id'), $outputs));
        self::assertSame('Bar', $outputs[1]->getElementsByTagName('name')->item(0)?->textContent);
    }

    public function testEachElementIsTheRootOfItsOwnDocument(): void
    {
        $outputs = $this->iterate(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 'product']);

        self::assertNotSame($outputs[0]->ownerDocument, $outputs[1]->ownerDocument);
        foreach ($outputs as $output) {
            self::assertSame($output, $output->ownerDocument?->documentElement);
        }
    }

    public function testElementsCanBeEvaluatedWithXpathIncludingAncestorNamespaces(): void
    {
        $outputs = $this->iterate(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 'product']);

        $transformer = new XpathEvaluatorTransformer();
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);
        $options = $resolver->resolve(['query' => ['id' => '@id', 'name' => 'name/text()', 'price' => 'g:price/text()']]);

        self::assertSame(['id' => '1', 'name' => 'Foo', 'price' => '10'], $transformer->transform($outputs[0], $options));
        self::assertSame(['id' => '2', 'name' => 'Bar', 'price' => '20'], $transformer->transform($outputs[1], $options));
    }

    public function testNodeNameIsTheQualifiedName(): void
    {
        $outputs = $this->iterate(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 'g:price']);

        self::assertSame(['10', '20'], array_map(static fn (\DOMElement $element): string => $element->textContent, $outputs));
    }

    public function testFilePathIsReadFromInputWhenNotSet(): void
    {
        $outputs = $this->iterate(['node_name' => 'product'], $this->tmpDir.'/catalog.xml');

        self::assertCount(2, $outputs);
    }

    public function testFilePathOptionHasPriorityOverInput(): void
    {
        $outputs = $this->iterate(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 'product'], $this->tmpDir.'/missing.xml');

        self::assertCount(2, $outputs);
    }

    public function testSkippedWhenNoElementMatches(): void
    {
        $task = new XmlStreamReaderTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 'unknown']);
        $task->initialize($state);
        $task->execute($state);

        self::assertTrue($state->isSkipped());
        self::assertNull($state->getOutput());
        self::assertFalse($task->next($state));
    }

    public function testFileIsReadAgainOnTheFollowingExecution(): void
    {
        $task = new XmlStreamReaderTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 'product']);
        $task->initialize($state);

        self::assertCount(2, $this->iterateTask($task, $state));
        self::assertCount(2, $this->iterateTask($task, $state));
    }

    public function testFinalizeReleasesTheFile(): void
    {
        $task = new XmlStreamReaderTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 'product']);
        $task->initialize($state);
        $task->execute($state);
        self::assertTrue($task->next($state));

        $task->finalize($state);

        self::assertFalse($task->next($state));
        $task->execute($state);
        self::assertInstanceOf(\DOMElement::class, $state->getOutput());
        self::assertSame('1', $state->getOutput()->getAttribute('id'));
    }

    public function testNextIsFalseBeforeTheFirstExecution(): void
    {
        self::assertFalse((new XmlStreamReaderTask())->next($this->createState(['node_name' => 'product'])));
    }

    public function testInvalidXmlThrowsAndReleasesTheFile(): void
    {
        file_put_contents($this->tmpDir.'/invalid.xml', '<catalog><product id="1"></catalog>');

        $task = new XmlStreamReaderTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/invalid.xml', 'node_name' => 'product']);
        $task->initialize($state);

        try {
            $this->iterateTask($task, $state);
            self::fail('An exception should have been thrown');
        } catch (\UnexpectedValueException $exception) {
            self::assertStringContainsString('Invalid XML in file "'.$this->tmpDir.'/invalid.xml"', $exception->getMessage());
            self::assertStringContainsString('line 1', $exception->getMessage());
        }
        self::assertFalse($task->next($state));
    }

    public function testEmptyFileThrows(): void
    {
        file_put_contents($this->tmpDir.'/empty.xml', '');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Invalid XML in file');

        $this->iterate(['file_path' => $this->tmpDir.'/empty.xml', 'node_name' => 'product']);
    }

    public function testMissingFileThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('does not exist or is not readable');

        $this->iterate(['file_path' => $this->tmpDir.'/missing.xml', 'node_name' => 'product']);
    }

    public function testMissingFilePathThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('No file path given');

        $this->iterate(['node_name' => 'product']);
    }

    public function testNodeNameIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->iterate(['file_path' => $this->tmpDir.'/catalog.xml']);
    }

    public function testNodeNameMustBeAString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->iterate(['file_path' => $this->tmpDir.'/catalog.xml', 'node_name' => 42]);
    }

    public function testFilePathMustBeAString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->iterate(['file_path' => 42, 'node_name' => 'product']);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<\DOMElement>
     */
    private function iterate(array $options, mixed $input = null): array
    {
        $task = new XmlStreamReaderTask();
        $state = $this->createState($options);
        $state->setInput($input);
        $task->initialize($state);

        return $this->iterateTask($task, $state);
    }

    /**
     * @return list<\DOMElement>
     */
    private function iterateTask(XmlStreamReaderTask $task, ProcessState $state): array
    {
        $outputs = [];
        do {
            $state->setSkipped(false);
            $state->setOutput(null);
            $task->execute($state);
            if (!$state->isSkipped()) {
                $output = $state->getOutput();
                self::assertInstanceOf(\DOMElement::class, $output);
                $outputs[] = $output;
            }
        } while ($task->next($state));

        return $outputs;
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
        $state->setTaskConfiguration(new TaskConfiguration('read', XmlStreamReaderTask::class, $options));

        return $state;
    }
}
