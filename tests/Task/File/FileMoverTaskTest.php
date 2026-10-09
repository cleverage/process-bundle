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
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessHistory;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Task\File\FileMoverTask;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

#[\PHPUnit\Framework\Attributes\CoversClass(FileMoverTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(AbstractConfigurableTask::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TaskConfiguration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ContextualOptionResolver::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessHistory::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(ProcessState::class)]
class FileMoverTaskTest extends TestCase
{
    private string $tmpDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        // No more_entropy: the directory name must not contain a dot (see makeFilenameUnique)
        $this->tmpDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.uniqid('file_mover_test_');
        $this->filesystem = new Filesystem();
        $this->filesystem->dumpFile($this->tmpDir.'/src/file.csv', 'source');
        $this->filesystem->mkdir($this->tmpDir.'/dest');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);
    }

    public function testMovesFileToDestinationPath(): void
    {
        $destination = $this->tmpDir.'/dest/renamed.csv';

        $state = $this->execute(['destination' => $destination], $this->tmpDir.'/src/file.csv');

        self::assertSame($destination, $state->getOutput());
        self::assertFileDoesNotExist($this->tmpDir.'/src/file.csv');
        self::assertSame('source', file_get_contents($destination));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function directoryDestinationProvider(): iterable
    {
        yield 'without trailing separator' => [''];
        yield 'with trailing separator' => [\DIRECTORY_SEPARATOR];
    }

    #[DataProvider('directoryDestinationProvider')]
    public function testKeepsOriginalFileNameWhenDestinationIsADirectory(string $suffix): void
    {
        $state = $this->execute(['destination' => $this->tmpDir.'/dest'.$suffix], $this->tmpDir.'/src/file.csv');

        self::assertSame($this->tmpDir.'/dest/file.csv', $state->getOutput());
        self::assertSame('source', file_get_contents($this->tmpDir.'/dest/file.csv'));
    }

    public function testThrowsWhenInputFileDoesNotExist(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('File does not exists');

        $this->execute(['destination' => $this->tmpDir.'/dest'], $this->tmpDir.'/src/missing.csv');
    }

    public function testThrowsWhenDestinationExistsWithoutOverwrite(): void
    {
        $this->filesystem->dumpFile($this->tmpDir.'/dest/file.csv', 'existing');

        try {
            $this->execute(['destination' => $this->tmpDir.'/dest'], $this->tmpDir.'/src/file.csv');
            self::fail('An IOException should have been thrown');
        } catch (IOException) {
            self::assertSame('existing', file_get_contents($this->tmpDir.'/dest/file.csv'));
            self::assertFileExists($this->tmpDir.'/src/file.csv');
        }
    }

    public function testOverwritesExistingDestinationWhenAllowed(): void
    {
        $this->filesystem->dumpFile($this->tmpDir.'/dest/file.csv', 'existing');

        $state = $this->execute(
            ['destination' => $this->tmpDir.'/dest', 'overwrite' => true],
            $this->tmpDir.'/src/file.csv',
        );

        self::assertSame($this->tmpDir.'/dest/file.csv', $state->getOutput());
        self::assertSame('source', file_get_contents($this->tmpDir.'/dest/file.csv'));
        self::assertFileDoesNotExist($this->tmpDir.'/src/file.csv');
    }

    public function testAutoincrementKeepsNameWhenDestinationIsFree(): void
    {
        $state = $this->execute(
            ['destination' => $this->tmpDir.'/dest', 'autoincrement' => true],
            $this->tmpDir.'/src/file.csv',
        );

        self::assertSame($this->tmpDir.'/dest/file.csv', $state->getOutput());
    }

    public function testAutoincrementAddsSuffixBeforeExtension(): void
    {
        $this->filesystem->dumpFile($this->tmpDir.'/dest/file.csv', 'existing');

        $state = $this->execute(
            ['destination' => $this->tmpDir.'/dest', 'autoincrement' => true],
            $this->tmpDir.'/src/file.csv',
        );

        self::assertSame($this->tmpDir.'/dest/file-1.csv', $state->getOutput());
        self::assertSame('source', file_get_contents($this->tmpDir.'/dest/file-1.csv'));
        self::assertSame('existing', file_get_contents($this->tmpDir.'/dest/file.csv'));
    }

    public function testAutoincrementIncrementsExistingSuffix(): void
    {
        $this->filesystem->dumpFile($this->tmpDir.'/dest/file.csv', 'existing');
        $this->filesystem->dumpFile($this->tmpDir.'/dest/file-1.csv', 'existing 1');

        $state = $this->execute(
            ['destination' => $this->tmpDir.'/dest', 'autoincrement' => true],
            $this->tmpDir.'/src/file.csv',
        );

        self::assertSame($this->tmpDir.'/dest/file-2.csv', $state->getOutput());
        self::assertSame('source', file_get_contents($this->tmpDir.'/dest/file-2.csv'));
    }

    public function testAutoincrementAppendsSuffixToFileWithoutExtension(): void
    {
        $this->filesystem->dumpFile($this->tmpDir.'/src/file', 'source');
        $this->filesystem->dumpFile($this->tmpDir.'/dest/file', 'existing');

        $state = $this->execute(
            ['destination' => $this->tmpDir.'/dest', 'autoincrement' => true],
            $this->tmpDir.'/src/file',
        );

        self::assertSame($this->tmpDir.'/dest/file-1', $state->getOutput());
        self::assertSame('source', file_get_contents($this->tmpDir.'/dest/file-1'));
    }

    public function testThrowsOnMissingDestinationOption(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->execute([], $this->tmpDir.'/src/file.csv');
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidOptionsProvider(): iterable
    {
        yield 'destination not a string' => [['destination' => ['foo']]];
        yield 'overwrite not a boolean' => [['destination' => 'foo', 'overwrite' => 'yes']];
        yield 'autoincrement not a boolean' => [['destination' => 'foo', 'autoincrement' => 1]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('invalidOptionsProvider')]
    public function testThrowsOnInvalidOptions(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->execute($options, $this->tmpDir.'/src/file.csv');
    }

    private function execute(array $options, mixed $input): ProcessState
    {
        $processConfiguration = new ProcessConfiguration('test', []);
        $state = new ProcessState($processConfiguration, new ProcessHistory($processConfiguration));
        $state->setContextualOptionResolver(new ContextualOptionResolver());
        $state->setContext([]);
        $state->setTaskConfiguration(new TaskConfiguration('move', FileMoverTask::class, $options));

        $task = new FileMoverTask();
        $task->initialize($state);
        $state->setInput($input);
        $task->execute($state);

        return $state;
    }
}
