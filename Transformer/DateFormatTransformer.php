<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2021 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Transformer aiming to take a date as an input (object or string) and format it according to options.
 * In input it takes any value understood by \DateTime.
 *
 * @example in YML config
 * transformers:
 *     date_format:
 *         format: Y-m-d
 *
 * @TODO deprecated v4.0 : remove string input
 * @TODO deprecated v4.0 : no false output
 */
class DateFormatTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param mixed $value
     * @param array $options
     *
     * @return mixed|string
     * @throws \Exception
     */
    public function transform($value, array $options = [])
    {
        if (!$value) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            $date = $value;
        } elseif (is_string($value)) {
            @trigger_error('String input will be deprecated in v4.0', E_USER_DEPRECATED);
            $date = new \DateTime($value);
        } else {
            throw new \UnexpectedValueException('Given value cannot be parsed into a date');
        }

        $result = $date->format($options['format']);
        if ($result === false) {
            @trigger_error('The date cannot be formatted, this will throw an error starting from v4.0', E_USER_DEPRECATED);
        }

        return $result;
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
     * @throws UndefinedOptionsException
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('format');
        $resolver->setAllowedTypes('format', 'string');
    }
}
