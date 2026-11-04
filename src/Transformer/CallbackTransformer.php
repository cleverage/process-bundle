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
 *
 * @final since 5.4
 */
class CallbackTransformer implements ConfigurableTransformerInterface
{
    /** Whether the deprecation of "additional_parameters" has been triggered: once */
    private static bool $additionalParametersDeprecated = false;

    /**
     * Must return the transformed $value.
     *
     * @param array<string, mixed> $options
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
                    throw new InvalidOptionsException(\sprintf('Callback option must be callable, %s given', self::describe($value)));
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
                // Once: it was triggered each time the options were resolved
                if ($value && !self::$additionalParametersDeprecated) {
                    self::$additionalParametersDeprecated = true;
                    trigger_deprecation(
                        'cleverage/process-bundle',
                        '2.0.4',
                        'The "additional_parameters" option is deprecated. Use "right_parameters" instead.'
                    );
                }

                return $value;
            }
        );
    }

    private static function describe(mixed $value): string
    {
        if (\is_string($value)) {
            return \sprintf('"%s"', $value);
        }
        if (\is_array($value) && array_is_list($value) && 2 === \count($value)
            && (\is_string($value[0]) || \is_object($value[0])) && \is_string($value[1])) {
            return \sprintf('"%s::%s"', \is_object($value[0]) ? $value[0]::class : $value[0], $value[1]);
        }

        return get_debug_type($value);
    }
}
