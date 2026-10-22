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

namespace CleverAge\ProcessBundle\Tests\ExpressionLanguage;

use CleverAge\ProcessBundle\ExpressionLanguage\PhpFunctionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[CoversClass(PhpFunctionProvider::class)]
class PhpFunctionProviderTest extends TestCase
{
    public function testThePhpFunctionsCanBeUsedInExpressions(): void
    {
        $provider = new PhpFunctionProvider(['strtoupper', 'str_contains']);
        $expressionLanguage = new ExpressionLanguage(null, [$provider]);

        self::assertSame(['strtoupper', 'str_contains'], array_map(static fn ($function): string => $function->getName(), $provider->getFunctions()));
        self::assertSame('FOO', $expressionLanguage->evaluate('strtoupper(value)', ['value' => 'foo']));
        self::assertTrue($expressionLanguage->evaluate('str_contains(value, "o")', ['value' => 'foo']));
    }
}
