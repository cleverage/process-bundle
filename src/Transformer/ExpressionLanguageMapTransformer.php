<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\ExpressionLanguage\ParsedExpression;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Parse an input using the Expression Language and returning a specific value upon a specific condition.
 */
class ExpressionLanguageMapTransformer implements ConfigurableTransformerInterface
{
    public function __construct(
        protected ExpressionLanguage $language
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['map']);
        $resolver->setAllowedTypes('map', ['array']);
        $resolver->setDefaults([
            'ignore_missing' => false,
            'keep_missing' => false,
        ]);
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);
        $resolver->setAllowedTypes('keep_missing', ['boolean']);
        $resolver->setNormalizer(
            'map',
            function (Options $options, $values): array {
                if (!\is_array($values)) {
                    throw new \UnexpectedValueException('The map must be an array');
                }
                $resolver = new OptionsResolver();
                $resolver->setRequired(['condition', 'output']);
                $resolver->setNormalizer(
                    'condition',
                    fn (Options $options, $value): ParsedExpression => $this->language->parse($value, ['data'])
                );
                $resolver->setNormalizer(
                    'output',
                    fn (Options $options, $value): ParsedExpression => $this->language->parse($value, ['data'])
                );
                $parsedValues = [];
                foreach ($values as $value) {
                    $parsedValues[] = $resolver->resolve($value);
                }

                return $parsedValues;
            }
        );
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        $input = [
            'data' => $value,
        ];
        foreach ($options['map'] as $mapItem) {
            if ($this->language->evaluate($mapItem['condition'], $input)) {
                return $this->language->evaluate($mapItem['output'], $input);
            }
        }

        if ($options['keep_missing']) {
            return $value;
        }
        if (!$options['ignore_missing']) {
            throw new \UnexpectedValueException("No expression accepting value '{$value}' in map");
        }

        return null;
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'expression_language_map';
    }
}
