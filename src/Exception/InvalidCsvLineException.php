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

namespace CleverAge\ProcessBundle\Exception;

/**
 * Thrown when a line of a CSV file cannot be read: it cannot be parsed, or its number of columns does not match the
 * headers. Carries the values read, to report the invalid line.
 */
class InvalidCsvLineException extends UnexpectedValueException
{
    /**
     * @param list<string|null>|null $values the values read by fgetcsv(), null when the line cannot be parsed
     */
    public function __construct(
        string $message,
        private readonly ?array $values = null,
    ) {
        parent::__construct($message);
    }

    /**
     * @return list<string|null>|null the values read by fgetcsv(), null when the line cannot be parsed
     */
    public function getValues(): ?array
    {
        return $this->values;
    }
}
