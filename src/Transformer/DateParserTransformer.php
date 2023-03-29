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
use Symfony\Component\OptionsResolver\OptionsResolver;
use UnexpectedValueException;

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
    public function transform(mixed $value, array $options = []): mixed
    {
        if (! $value || $value instanceof DateTime) {
            return $value;
        }

        $date = DateTime::createFromFormat($options['format'], $value);

        if (! $date) {
            throw new UnexpectedValueException('Given value cannot be parsed into a date');
        }

        return $date;
    }

    public function getCode(): string
    {
        return 'date_parser';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('format');
        $resolver->setAllowedTypes('format', 'string');
    }
}
