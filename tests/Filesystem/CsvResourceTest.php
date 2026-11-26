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

namespace CleverAge\ProcessBundle\Tests\Filesystem;

use CleverAge\ProcessBundle\Exception\InvalidCsvLineException;
use CleverAge\ProcessBundle\Filesystem\CsvResource;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(CsvResource::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(InvalidCsvLineException::class)]
class CsvResourceTest extends TestCase
{
    public function testHeadersAreReadFromTheFirstLine(): void
    {
        $csv = new CsvResource($this->createResource("\xEF\xBB\xBFa;b\n1;2\n"), ';');

        self::assertSame(['a', 'b'], $csv->getHeaders());
        self::assertSame(['a' => '1', 'b' => '2'], $csv->readLine());
    }

    public function testBlankFirstLineIsRejected(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Unable to read headers');

        new CsvResource($this->createResource("\na;b\n"), ';');
    }

    public function testLineWithAnotherNumberOfColumnsThrowsWithItsValues(): void
    {
        $csv = new CsvResource($this->createResource("a;b\n1;2;3\n"), ';');

        try {
            $csv->readLine();
            self::fail('An InvalidCsvLineException should have been thrown');
        } catch (InvalidCsvLineException $e) {
            self::assertStringStartsWith('Number of columns not matching on line 2 for ', $e->getMessage());
            self::assertSame(['1', '2', '3'], $e->getValues());
        }
    }

    public function testTellReturnsThePositionInTheResource(): void
    {
        $csv = new CsvResource($this->createResource("a;b\n1;2\n"), ';');

        self::assertSame(4, $csv->tell());
    }

    /**
     * @return resource
     */
    private function createResource(string $content)
    {
        $resource = fopen('php://memory', 'w+');
        self::assertIsResource($resource);
        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }
}
