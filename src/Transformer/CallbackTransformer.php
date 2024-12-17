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

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Convert input based on a callback.
 */
class CallbackTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value.
     */
    public function transform(mixed $value, array $options = []): mixed
    {
        if ((is_countable($options['additional_parameters']) ? \count($options['additional_parameters']) : 0)
            && !(is_countable($options['right_parameters']) ? \count($options['right_parameters']) : 0)) {
            $options['right_parameters'] = $options['additional_parameters'];
        }

        $parameters = $options['left_parameters'];
        array_push($parameters, $value, ...$options['right_parameters']);

        return \call_user_func_array($options['callback'], $parameters);
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'callback';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['callback']);
        $resolver->setAllowedTypes('callback', ['string', 'array']);
        $resolver->setNormalizer(
            'callback',
            static function (Options $options, $value): callable {
                if (!\is_callable($value)) {
                    throw new InvalidOptionsException('Callback option must be callable');
                }

                return $value;
            }
        );
        $resolver->setDefaults(
            [
                'left_parameters' => [],
                'right_parameters' => [],
                'additional_parameters' => [],
            ]
        );
        $resolver->setAllowedTypes('left_parameters', ['array']);
        $resolver->setAllowedTypes('right_parameters', ['array']);
        $resolver->setAllowedTypes('additional_parameters', ['array']);

        $resolver->setNormalizer(
            'additional_parameters',
            static function (Options $options, $value) {
                if ($value) {
                    @trigger_error(
                        'The "additional_parameters" option is deprecated. Use "right_parameters" instead.',
                        \E_USER_DEPRECATED
                    );
                }

                return $value;
            }
        );
    }
}
