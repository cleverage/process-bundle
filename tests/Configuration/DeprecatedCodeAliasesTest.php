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

namespace CleverAge\ProcessBundle\Tests\Configuration;

use CleverAge\ProcessBundle\Configuration\DeprecatedCodeAliases;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeprecatedCodeAliases::class)]
class DeprecatedCodeAliasesTest extends TestCase
{
    /**
     * The former names follow the key normalization of the Symfony configuration: the dashes were replaced unless the
     * code also had an underscore, or the code with underscores was also defined.
     */
    public function testCreate(): void
    {
        self::assertSame(
            ['demo.x_y' => 'demo.x-y', 'a_b_c' => 'a-b-c'],
            DeprecatedCodeAliases::create(['demo.x-y', 'a-b-c', 'no_dash', 'dash-and_underscore', 'both-defined', 'both_defined', 'plain', 0])
        );
    }
}
