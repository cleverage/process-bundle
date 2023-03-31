<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Allow to inject a set of PHP function into an ExpressionLanguage instance
 */
class PhpFunctionProvider implements ExpressionFunctionProviderInterface
{
    public function __construct(
        protected array $functions
    ) {
    }

    /**
     * @return ExpressionFunction[]
     */
    public function getFunctions(): array
    {
        return array_map(fn ($func): ExpressionFunction => ExpressionFunction::fromPhp($func), $this->functions);
    }
}
