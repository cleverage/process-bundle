<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Simply delete the file passed as input
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class FileRemoverTask implements TaskInterface
{
    /**
     * @param ProcessState $state
     *
     * @throws \Symfony\Component\Filesystem\Exception\IOException
     */
    public function execute(ProcessState $state)
    {
        $fs = new Filesystem();
        $fs->remove($state->getInput());
    }
}
