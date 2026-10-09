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
use CleverAge\ProcessBundle\Task\File\Csv\CsvWriterTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(CsvWriterTask::class)]
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
class CsvWriterTaskTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('csv_writer_test_', true);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->tmpDir);
    }

    public function testWritesHeadersFromFirstInputKeysAndOutputsFilePath(): void
    {
        $filePath = $this->tmpDir.'/out.csv';
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $filePath]);

        $this->write($task, $state, [['id' => 1, 'name' => 'foo'], ['id' => 2, 'name' => 'bar']]);

        self::assertSame($filePath, $state->getOutput());
        self::assertSame("id;name\n1;foo\n2;bar\n", file_get_contents($filePath));
    }

    public function testCreatesMissingParentDirectory(): void
    {
        $filePath = $this->tmpDir.'/sub/dir/out.csv';
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $filePath]);

        $this->write($task, $state, [['id' => 1]]);

        self::assertFileExists($filePath);
    }

    public function testWritesColumnsInStaticHeadersOrder(): void
    {
        $filePath = $this->tmpDir.'/out.csv';
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $filePath, 'headers' => ['name', 'id']]);

        $this->write($task, $state, [['id' => 1, 'name' => 'foo']]);

        self::assertSame("name;id\nfoo;1\n", file_get_contents($filePath));
    }

    public function testDoesNotWriteHeadersWhenDisabled(): void
    {
        $filePath = $this->tmpDir.'/out.csv';
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $filePath, 'write_headers' => false]);

        $this->write($task, $state, [['id' => 1, 'name' => 'foo']]);

        self::assertSame("1;foo\n", file_get_contents($filePath));
    }

    public function testUsesDelimiterEnclosureAndSplitCharacterOptions(): void
    {
        $filePath = $this->tmpDir.'/out.csv';
        $task = new CsvWriterTask();
        $state = $this->createState([
            'file_path' => $filePath,
            'delimiter' => ',',
            'enclosure' => "'",
            'split_character' => '/',
        ]);

        $this->write($task, $state, [['id' => 1, 'tags' => ['a', 'b'], 'label' => 'x,y']]);

        self::assertSame("id,tags,label\n1,a/b,'x,y'\n", file_get_contents($filePath));
    }

    public function testImplodesArrayValuesWithDefaultSplitCharacter(): void
    {
        $filePath = $this->tmpDir.'/out.csv';
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $filePath]);

        $this->write($task, $state, [['tags' => ['a', 'b', 'c']]]);

        self::assertSame("tags\na|b|c\n", file_get_contents($filePath));
    }

    public function testDefaultModeOverwritesExistingFile(): void
    {
        $filePath = $this->tmpDir.'/out.csv';
        (new Filesystem())->dumpFile($filePath, "old;content\n");
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $filePath]);

        $this->write($task, $state, [['id' => 1]]);

        self::assertSame("id\n1\n", file_get_contents($filePath));
    }

    public function testAppendModeDoesNotRewriteHeadersInNonEmptyFile(): void
    {
        $filePath = $this->tmpDir.'/out.csv';
        (new Filesystem())->dumpFile($filePath, "id;name\n1;foo\n");
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $filePath, 'mode' => 'ab', 'headers' => ['id', 'name']]);

        $this->write($task, $state, [['id' => 2, 'name' => 'bar']]);

        self::assertSame("id;name\n1;foo\n2;bar\n", file_get_contents($filePath));
    }

    public function testAppendModeWritesHeadersInEmptyFile(): void
    {
        $filePath = $this->tmpDir.'/out.csv';
        (new Filesystem())->dumpFile($filePath, '');
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $filePath, 'mode' => 'ab', 'headers' => ['id']]);

        $this->write($task, $state, [['id' => 1]]);

        self::assertSame("id\n1\n", file_get_contents($filePath));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function placeholderProvider(): iterable
    {
        yield 'date' => ['out_{date}.csv', '/^out_\d{8}\.csv$/'];
        yield 'date_time' => ['out_{date_time}.csv', '/^out_\d{8}_\d{6}\.csv$/'];
        yield 'timestamp' => ['out_{timestamp}.csv', '/^out_\d{10,}\.csv$/'];
        yield 'unique_token' => ['out_{unique_token}.csv', '/^out_[0-9a-f]+\.\d+\.csv$/'];
    }

    #[DataProvider('placeholderProvider')]
    public function testReplacesPlaceholdersInFilePath(string $fileName, string $expectedPattern): void
    {
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/'.$fileName]);

        $this->write($task, $state, [['id' => 1]]);

        $output = $state->getOutput();
        self::assertIsString($output);
        self::assertSame($this->tmpDir, \dirname($output));
        self::assertMatchesRegularExpression($expectedPattern, basename($output));
        self::assertFileExists($output);
    }

    public function testDatePlaceholderIsReplacedByCurrentDate(): void
    {
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/out_{date}.csv']);
        $expectedDate = date('Ymd');

        $this->write($task, $state, [['id' => 1]]);

        self::assertSame($this->tmpDir.'/out_'.$expectedDate.'.csv', $state->getOutput());
    }

    public function testThrowsWhenInputIsNotAnArrayWithStaticHeaders(): void
    {
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/out.csv', 'headers' => ['id']]);
        $task->initialize($state);
        $state->setInput('not an array');

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Input value is not an array');
        $task->execute($state);
    }

    public function testThrowsWhenInputHasMissingColumn(): void
    {
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/out.csv', 'headers' => ['id', 'name']]);
        $task->initialize($state);
        $state->setInput(['id' => 1, 'other' => 'foo']);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Missing column name');
        $task->execute($state);
    }

    public function testThrowsWhenInputHasExtraColumn(): void
    {
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/out.csv']);
        $task->initialize($state);
        $state->setInput(['id' => 1]);
        $task->execute($state);
        $state->setInput(['id' => 2, 'name' => 'extra']);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('invalid number of columns');
        $task->execute($state);
    }

    public function testThrowsOnMissingFilePathOption(): void
    {
        $task = new CsvWriterTask();
        $state = $this->createState([]);

        $this->expectException(MissingOptionsException::class);
        $task->initialize($state);
    }

    public function testThrowsOnInvalidHeadersOptionType(): void
    {
        $task = new CsvWriterTask();
        $state = $this->createState(['file_path' => $this->tmpDir.'/out.csv', 'headers' => 'id']);

        $this->expectException(InvalidOptionsException::class);
        $task->initialize($state);
    }

    /**
     * Mimics the ProcessManager behaviour for a blocking task: execute for each input, then proceed and finalize.
     *
     * @param list<mixed> $inputs
     */
    private function write(CsvWriterTask $task, ProcessState $state, array $inputs): void
    {
        $task->initialize($state);
        foreach ($inputs as $input) {
            $state->setInput($input);
            $task->execute($state);
        }
        $task->proceed($state);
        $task->finalize($state);
    }

    private function createState(array $options): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('write', CsvWriterTask::class, $options));

        return $state;
    }
}
