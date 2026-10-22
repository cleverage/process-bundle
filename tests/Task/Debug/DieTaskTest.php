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

namespace CleverAge\ProcessBundle\Tests\Task\Debug;

use CleverAge\ProcessBundle\Task\Debug\DieTask;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\PhpProcess;

/**
 * DieTask calls exit: it is executed in a separate PHP process to keep PHPUnit alive.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(DieTask::class)]
class DieTaskTest extends TestCase
{
    public function testExecutionTerminatesTheScriptImmediately(): void
    {
        $autoload = var_export(\dirname(__DIR__, 3).'/vendor/autoload.php', true);
        $script = <<<PHP
            <?php
            require {$autoload};

            use CleverAge\ProcessBundle\Configuration\ProcessConfiguration;
            use CleverAge\ProcessBundle\Model\ProcessHistory;
            use CleverAge\ProcessBundle\Model\ProcessState;
            use CleverAge\ProcessBundle\Task\Debug\DieTask;

            \$configuration = new ProcessConfiguration('test', []);
            \$state = new ProcessState(\$configuration, new ProcessHistory(\$configuration));
            \$state->setInput('foo');
            echo 'before;';
            try {
                (new DieTask())->execute(\$state);
            } finally {
                echo 'finally;';
            }
            echo 'after;';
            PHP;

        $process = new PhpProcess($script);
        $process->run();

        self::assertSame('', $process->getErrorOutput());
        self::assertSame(0, $process->getExitCode());
        self::assertSame('before;', $process->getOutput());
    }
}
