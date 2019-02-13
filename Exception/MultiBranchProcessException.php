<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Exception;

/**
 * Thrown when multiple independent branches are found in a process
 *
 * @deprecated, we won't send an error for now
 */
class MultiBranchProcessException extends \UnexpectedValueException implements ProcessExceptionInterface
{
    /**
     * @param string $processCode
     * @param array  $branches
     *
     * @return MultiBranchProcessException
     */
    public static function create($processCode, array $branches)
    {
        $branchesStr = [];
        foreach ($branches as $branch) {
            $branchesStr[] = '['.implode(', ', $branch).']';
        }

        $errorStr = '['.implode(', ', $branchesStr).']';
        $errorStr = "Process {$processCode} contains multiple independent branches : {$errorStr}, ";
        $errorStr .= 'which is not allowed. Please create a process for each branch';

        return new self($errorStr);
    }
}
