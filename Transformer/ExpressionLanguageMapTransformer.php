<?php declare(strict_types=1);
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
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Parse an input using the Expression Language and returning a specific value upon a specific condition
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ExpressionLanguageMapTransformer implements ConfigurableTransformerInterface
{
    /** @var ExpressionLanguage */
    protected $language;

    /**
     * @param ExpressionLanguage $language
     */
    public function __construct(ExpressionLanguage $language)
    {
        $this->language = $language;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'map',
            ]
        );
        $resolver->setAllowedTypes('map', ['array']);
        $resolver->setDefaults(
            [
                'ignore_missing' => false,
                'keep_missing' => false,
            ]
        );
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);
        $resolver->setAllowedTypes('keep_missing', ['boolean']);
        $resolver->setNormalizer(
            'map',
            function (Options $options, $values) {
                if (!is_array($values)) {
                    throw new \UnexpectedValueException('The map must be an array');
                }
                $resolver = new OptionsResolver();
                $resolver->setRequired(
                    [
                        'condition',
                        'output',
                    ]
                );
                $resolver->setNormalizer(
                    'condition',
                    function (Options $options, $value) {
                        return $this->language->parse($value, ['data']);
                    }
                );
                $resolver->setNormalizer(
                    'output',
                    function (Options $options, $value) {
                        return $this->language->parse($value, ['data']);
                    }
                );
                $parsedValues = [];
                foreach ($values as $value) {
                    $parsedValues[] = $resolver->resolve($value);
                }

                return $parsedValues;
            }
        );
    }

    /**
     * Must return the transformed $value
     *
     * @param mixed $value
     * @param array $options
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        $input = ['data' => $value];
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
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode(): string
    {
        return 'expression_language_map';
    }
}
