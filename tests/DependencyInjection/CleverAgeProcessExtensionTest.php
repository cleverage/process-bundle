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

namespace CleverAge\ProcessBundle\Tests\DependencyInjection;

use CleverAge\ProcessBundle\DependencyInjection\CleverAgeProcessExtension;
use CleverAge\ProcessBundle\DependencyInjection\Configuration;
use CleverAge\ProcessBundle\Model\ProcessStopCheckerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(CleverAgeProcessExtension::class)]
#[UsesClass(Configuration::class)]
class CleverAgeProcessExtensionTest extends TestCase
{
    public function testStopCheckersAreInjectedInTheProcessManager(): void
    {
        $container = new ContainerBuilder();
        (new CleverAgeProcessExtension())->load([], $container);

        $autoconfigured = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(ProcessStopCheckerInterface::class, $autoconfigured);
        self::assertSame(['cleverage_process.stop_checker' => [[]]], $autoconfigured[ProcessStopCheckerInterface::class]->getTags());

        $argument = $container->getDefinition('cleverage_process.manager.process')->getArgument(6);
        self::assertInstanceOf(TaggedIteratorArgument::class, $argument);
        self::assertSame('cleverage_process.stop_checker', $argument->getTag());
    }
}
