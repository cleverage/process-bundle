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

namespace CleverAge\ProcessBundle\Task\File;

use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Simply delete the file passed as input.
 */
class FileRemoverTask implements TaskInterface
{
    public function execute(ProcessState $state): void
    {
        $fs = new Filesystem();
        $fs->remove($state->getInput());
    }
}
