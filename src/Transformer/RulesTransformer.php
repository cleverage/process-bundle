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

use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use InvalidArgumentException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Uses a set of rules to conditionally transform a value
 */
class RulesTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    public function __construct(
        TransformerRegistry $transformerRegistry,
        protected ExpressionLanguage $language
    ) {
        $this->transformerRegistry = $transformerRegistry;
    }

    public function transform($value, array $options = [])
    {
        foreach ($options['rules_set'] as $rule) {
            if ($this->matchRule($value, $rule, $options['use_value_as_variables'])) {
                if ($rule['set_null']) {
                    return null;
                } elseif ($rule['constant'] !== null) {
                    return $rule['constant'];
                }
                return $this->applyTransformers($rule['transformers'], $value);
            }
        }

        return $value;
    }

    public function getCode(): string
    {
        return 'rules';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('use_value_as_variables', false);
        $resolver->setAllowedTypes('use_value_as_variables', 'bool');

        $resolver->setDefault('expression_variables', ['value']);
        $resolver->setAllowedTypes('expression_variables', ['null', 'array']);

        $resolver->setRequired('rules_set');
        $resolver->setAllowedTypes('rules_set', 'array');
        $resolver->setNormalizer('rules_set', function (Options $options, $conditionSet): array {
            $rules = array_map(function ($item) use ($options): array {
                $resolver = new OptionsResolver();
                $this->configureRuleOptions($resolver, $options['expression_variables']);

                return $resolver->resolve($item);
            }, $conditionSet);

            // Check default rule an order
            $hasFoundDefault = false;
            foreach ($rules as $rule) {
                if ($rule['default']) {
                    if ($hasFoundDefault) {
                        throw new InvalidArgumentException('Rules set cannot have more than 2 default rules');
                    }
                    $hasFoundDefault = true;
                }

                if ($hasFoundDefault && $rule['condition'] !== null) {
                    throw new InvalidArgumentException('A conditional rule cannot be placed after a default rule');
                }
            }

            return $rules;
        });
    }

    /**
     * Configure options for one "rule" block
     */
    public function configureRuleOptions(OptionsResolver $resolver, ?array $expressionVariables = null): void
    {
        $resolver->setDefaults([
            'condition' => null,
            'default' => false,
            'constant' => null,
            'set_null' => false,
        ]);
        $resolver->setAllowedTypes('condition', ['null', 'string', ParsedExpression::class]);
        $resolver->setAllowedTypes('default', 'bool');
        $resolver->setAllowedTypes('set_null', 'bool');

        $expressionNormalizer = function (Options $options, $expression) use ($expressionVariables) {
            if (is_array($expressionVariables) && $expression !== null) {
                return $this->language->parse($expression, $expressionVariables);
            }
            return $expression;
        };

        $resolver->setNormalizer('condition', $expressionNormalizer);
        $resolver->setNormalizer('default', function (Options $options, $value) {
            if ($value && $options['condition']) {
                throw new InvalidArgumentException(
                    'A rule cannot have a condition and be the default in the same time'
                );
            }

            return $value;
        });

        $this->configureTransformersOptions($resolver);
    }

    /**
     * Test if a value match a rule
     */
    protected function matchRule(mixed $value, string|ParsedExpression $rule, bool $useValueAsVariable): bool
    {
        if ($rule['condition'] !== null) {
            $expressionValues = $useValueAsVariable ? $value : [
                'value' => $value,
            ];

            return $this->language->evaluate($rule['condition'], $expressionValues);
        }

        return $rule['default'];
    }
}
