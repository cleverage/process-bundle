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

/*
 * The cleverage:process:execute command receiving SIGTERM when the process starts: executed in a PHP process of its
 * own by SignalTest.
 */

use CleverAge\ProcessBundle\Tests\App\TestKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$kernel = new TestKernel('test', false);
$kernel->boot();
/** @var EventDispatcherInterface $eventDispatcher */
$eventDispatcher = $kernel->getContainer()->get('event_dispatcher');
$eventDispatcher->addListener('cleverage_process.start', static function (): void {
    posix_kill((int) getmypid(), \SIGTERM);
});

exit((new Application($kernel))->run(new ArgvInput(['console', 'cleverage:process:execute', 'test.run', '--output-format=json-stream'])));
