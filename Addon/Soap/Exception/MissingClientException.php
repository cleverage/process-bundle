<?php
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2018 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Addon\Soap\Exception;

use CleverAge\ProcessBundle\Exception\ProcessExceptionInterface;

/**
 * Exception thrown when trying to fetch a missing Soap client
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class MissingClientException extends \UnexpectedValueException implements ProcessExceptionInterface
{
    /**
     * @param string $code
     *
     * @return MissingClientException
     */
    public static function create($code)
    {
        $errorStr = "No Soap client with code : {$code}";

        return new self($errorStr);
    }
}
