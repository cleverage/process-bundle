<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Exception;

/**
 * Exception thrown when trying to fetch a missing process
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class MissingProcessException extends \UnexpectedValueException implements ProcessExceptionInterface
{
    /**
     * @param string $code
     *
     * @return MissingProcessException
     */
    public static function create($code): self
    {
        $errorStr = "No process with code : {$code}";

        return new self($errorStr);
    }
}
