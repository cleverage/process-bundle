<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Convert input based on a callback
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CallbackTransformer implements ConfigurableTransformerInterface
{
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
        if (count($options['additional_parameters'])
            && !count($options['right_parameters'])) {
            $options['right_parameters'] = $options['additional_parameters'];
        }

        $parameters = $options['left_parameters'];
        array_push($parameters, $value, ...$options['right_parameters']);

        return \call_user_func_array($options['callback'], $parameters);
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'callback';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'callback',
            ]
        );
        $resolver->setAllowedTypes('callback', ['string', 'array']);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'callback',
            function (Options $options, $value) {
                if (!\is_callable($value)) {
                    throw new InvalidOptionsException(
                        'Callback option must be callable'
                    );
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

        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'additional_parameters',
            function (Options $options, $value) {
                if ($value) {
                    @trigger_error('The "additional_parameters" option is deprecated. Use "right_parameters" instead.', E_USER_DEPRECATED);
                }

                return $value;
            }
        );
    }
}
