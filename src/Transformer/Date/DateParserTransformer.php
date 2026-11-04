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

namespace CleverAge\ProcessBundle\Transformer\Date;

use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Transformer\ConfigurableTransformerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Transformer aiming to take a date as an input (object or a format defined string) to strictly output a \DateTime.
 *
 * @example in YML config
 * transformers:
 *     date_parser:
 *         format: Y-m-d
 *
 * @final since 5.4
 */
class DateParserTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function transform(mixed $value, array $options = []): mixed
    {
        if (!$value || $value instanceof \DateTime) {
            return $value;
        }

        if ($value instanceof \DateTimeImmutable) {
            return \DateTime::createFromImmutable($value);
        }

        if (\is_int($value) || \is_float($value) || \is_bool($value) || $value instanceof \Stringable) {
            $value = (string) $value;
        } elseif (!\is_string($value)) {
            throw new UnexpectedValueException(\sprintf('The date_parser transformer expects a string, %s given.', get_debug_type($value)));
        }

        $date = \DateTime::createFromFormat($options['format'], $value);

        if (!$date) {
            throw new UnexpectedValueException('Given value cannot be parsed into a date');
        }
        // An out-of-range value (e.g. "2024-02-31", "25:61") is parsed with a warning, the date overflowing to the next
        // month or day
        $errors = \DateTime::getLastErrors();
        $warnings = \is_array($errors) ? $errors['warnings'] : [];
        if (str_contains($options['format'], '+')) {
            // The "+" of the format allows trailing data, reported as a warning
            $warnings = array_filter($warnings, static fn (string $warning): bool => 'Trailing data' !== $warning);
        }
        if ([] !== $warnings) {
            throw new UnexpectedValueException(\sprintf('Given value "%s" is not a valid date: %s', $value, implode(', ', $warnings)));
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
