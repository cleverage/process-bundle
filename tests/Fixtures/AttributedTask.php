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

namespace CleverAge\ProcessBundle\Tests\Fixtures;

use CleverAge\ProcessBundle\Attribute\AsTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Model\TaskInterface;

#[AsTask(code: 'app_attributed')]
final class AttributedTask implements TaskInterface
{
    public function execute(ProcessState $state): void
    {
    }
}
