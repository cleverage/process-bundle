<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Transformer;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;

/**
 * Tests for Date transformers
 */
class DateTransformersTest extends AbstractProcessTest
{

    /**
     * Assert a date string can be formatted into another string
     */
    public function testDateFormatString()
    {
        $result = $this->processManager->execute('test.date_transformers.date_format', '2001-01-01T00:00:00+00:00');
        self::assertEquals('2001-01-01', $result);
    }

    /**
     * Assert a date object can be formatted into a string
     */
    public function testDateFormatObject()
    {
        $date = \DateTime::createFromFormat(DATE_ATOM, '2001-01-02T00:00:00+00:00');
        $result = $this->processManager->execute('test.date_transformers.date_format', $date);
        self::assertEquals('2001-01-02', $result);
    }

    /**
     * Assert a date can be parsed using a given format
     */
    public function testDateParser()
    {
        $date = \DateTime::createFromFormat('d/m/Y', '01/01/2001');
        $result = $this->processManager->execute('test.date_transformers.date_parser', '2001-01-01');

        // There could be a 1s difference, depending on execution time...
        $date->setTime(0, 0);
        $result->setTime(0, 0);

        self::assertInstanceOf(\DateTime::class, $result);
        if ($result instanceof \DateTime) {
            self::assertEquals($date->getTimestamp(), $result->getTimestamp());
        }
    }

    /**
     * Assert that a date is not parsed if the format doesn't match
     *
     * @expectedException \RuntimeException
     */
    public function testDateParserError()
    {
        $this->processManager->execute('test.date_transformers.date_parser', '2001-01-01T00:00:00+00:00');
    }

    /**
     * Assert date parser & formatter can be chained to transform a date string into another
     */
    public function testDateParseFormat()
    {
        $result = $this->processManager->execute(
            'test.date_transformers.date_parse_format',
            '2001-01-01T00:00:00+00:00'
        );
        self::assertEquals('2001-01-01', $result);
    }
}
