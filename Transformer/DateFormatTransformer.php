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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @todo vclavreul comment this class
 */
class DateFormatTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param mixed $value
     * @param array $options
     *
     * @return mixed|string
     */
    public function transform($value, array $options = [])
    {
        if (!$value) {
            return $value;
        }

        if ($value instanceof \DateTime) {
            $date = $value;
        } elseif (is_string($value)) {
            $date = new \DateTime($value);
        } else {
            throw new \UnexpectedValueException("Given value cannot be parsed into a date");
        }

        return $date->format($options['format']);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return 'date_format';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('format');
        $resolver->setAllowedTypes('format', 'string');
    }
}
