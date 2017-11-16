<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Exception;

/**
 * Thrown when multiple independent branches are found in a process
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
            $branchesStr[] = '[' . implode(', ', $branch) . ']';
        }

        $errorStr = '[' . implode(', ', $branchesStr) . ']';
        $errorStr = "Process {$processCode} contains multiple independent branches : {$errorStr}, ";
        $errorStr .= "which is not allowed. Please create a process for each branch";

        return new MultiBranchProcessException($errorStr);
    }
}
