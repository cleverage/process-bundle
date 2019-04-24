<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Debug;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Symfony\Component\VarDumper\VarDumper;

/**
 * Dump the content of the input
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class DebugTask implements TaskInterface
{
    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        if (class_exists(VarDumper::class)) {
            VarDumper::dump($state->getInput());
        }

        $state->setOutput($state->getInput());
    }
}
