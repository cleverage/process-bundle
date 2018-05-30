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
 * Transformer aiming to take a date as an input (object or a format defined string) to strictly output aa \DateTime
 *
 * @example in YML config
 * transformers:
 *     date_parser:
 *         format: Y-m-d
 */
class DateParserTransformer implements ConfigurableTransformerInterface
{

    /**
     * @param mixed $value
     * @param array $options
     *
     * @return mixed|string
     */
    public function transform($value, array $options = [])
    {
        if (!$value || $value instanceof \DateTime) {
            return $value;
        }

        $date = \DateTime::createFromFormat($options['format'], $value);

        if (!$date) {
            throw new \UnexpectedValueException("Given value cannot be parsed into a date");
        }

        return $date;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return 'date_parser';
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
