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

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvaluatorTransformer implements ConfigurableTransformerInterface
{
    protected ExpressionLanguage $language;

    public function __construct()
    {
        $this->language = new ExpressionLanguage();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Allow to cache the parsing by statically defining variables
        $resolver->setDefault('variables', null);
        $resolver->addAllowedTypes('variables', ['null', 'array']);

        $resolver->setRequired(['expression']);
        $resolver->setAllowedTypes('expression', ['string', ParsedExpression::class]);
        $resolver->setNormalizer(
            'expression',
            function (Options $options, $expression) {
                if (is_array($options['variables'])) {
                    return $this->language->parse($expression, $options['variables']);
                }

                return $expression;
            }
        );
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function transform($value, array $options = [])
    {
        return $this->language->evaluate($options['expression'], $value);
    }

    public function getCode(): string
    {
        return 'evaluator';
    }
}
