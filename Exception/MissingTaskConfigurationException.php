<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Exception;

/**
 * Exception thrown when trying to fetch a missing task configuration
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class MissingTaskConfigurationException extends \UnexpectedValueException implements ProcessExceptionInterface
{
    /**
     * @param string $code
     *
     * @return MissingTaskConfigurationException
     */
    public static function create($code): self
    {
        $errorStr = "No task configuration with code : {$code}";

        return new self($errorStr);
    }
}
