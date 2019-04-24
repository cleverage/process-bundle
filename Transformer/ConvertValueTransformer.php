<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Transform a value to another value based on a conversion table
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ConvertValueTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value
     *
     * @param mixed $value
     * @param array $options
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        if (null === $value) {
            return $value;
        }

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        if (!is_string($value) && !is_int($value)) { // If not a valid array index
            if (!$options['auto_cast']) {
                $type = gettype($value);
                throw new \UnexpectedValueException(
                    "Value of type {$type} is not a valid array index, set auto_cast to true to cast it to a string"
                );
            }
            $value = (string) $value; // Let's cast it to string
        }

        if (!array_key_exists($value, $options['map'])) {
            if ($options['keep_missing']) {
                return $value;
            }
            if (!$options['ignore_missing']) {
                throw new \UnexpectedValueException("Missing value in map '{$value}'");
            }

            return null;
        }

        return $options['map'][$value];
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'convert_value';
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
                'map',
            ]
        );
        $resolver->setAllowedTypes('map', ['array']);
        $resolver->setDefaults(
            [
                'ignore_missing' => false,
                'keep_missing' => false,
                'auto_cast' => false,
            ]
        );
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);
        $resolver->setAllowedTypes('keep_missing', ['boolean']);
        $resolver->setAllowedTypes('auto_cast', ['boolean']);
    }
}
