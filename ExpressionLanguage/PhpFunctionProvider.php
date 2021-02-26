<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Allow to inject a set of PHP function into an ExpressionLanguage instance
 *
 * @internal
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 */
class PhpFunctionProvider implements ExpressionFunctionProviderInterface
{
    /** @var array */
    protected $functions;

    /**
     * PhpFunctionProvider constructor.
     *
     * @param array $functions
     */
    public function __construct(array $functions)
    {
        $this->functions = $functions;
    }

    /**
     * @return ExpressionFunction[]
     */
    public function getFunctions()
    {
        return array_map(function ($func) {
            return ExpressionFunction::fromPhp($func);
        }, $this->functions);
    }
}
