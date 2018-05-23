<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Exception;

/**
 * Runtime error that should wrap any Transformation error
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class TransformerException extends \RuntimeException implements ProcessExceptionInterface
{
    /**
     * {@inheritDoc}
     */
    public function __construct($targetProperty, $code = 0, \Throwable $previous = null)
    {
        $m = "Transformation have failed for target property '{$targetProperty}'";
        if ($previous) {
            $m .= ": {$previous->getMessage()}";
        }

        parent::__construct($m, $code, $previous);
    }
}
