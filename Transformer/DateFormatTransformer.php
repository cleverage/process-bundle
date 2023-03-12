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

use DateTime;
use DateTimeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

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
     *
     * @return mixed|string
     */
    public function transform($value, array $options = [])
    {
        if (! $value) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            $date = $value;
        } elseif (is_string($value)) {
            @trigger_error('String input will be deprecated in v4.0', E_USER_DEPRECATED);
            $date = new DateTime($value);
        } else {
            throw new UnexpectedValueException('Given value cannot be parsed into a date');
        }

        $result = $date->format($options['format']);
        if ($result === false) {
            @trigger_error(
                'The date cannot be formatted, this will throw an error starting from v4.0',
                E_USER_DEPRECATED
            );
        }

        return $result;
    }

    public function getCode(): string
    {
        return 'date_format';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('format');
        $resolver->setAllowedTypes('format', 'string');
    }
}
