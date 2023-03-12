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
 */
class DateFormatTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param mixed $value
     */
    public function transform($value, array $options = []): mixed
    {
        if (! $value) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            $date = $value;
        } else {
            throw new UnexpectedValueException('Given value cannot be parsed into a date');
        }

        return $date->format($options['format']);
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
