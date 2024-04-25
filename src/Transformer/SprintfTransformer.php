<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Use sprintf() function to format string.
 */
class SprintfTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = []): string
    {
        if (!\is_array($value)) {
            $value = [$value];
        }

        return vsprintf($options['format'], $value);
    }

    public function getCode(): string
    {
        return 'sprintf';
    }

    /**
     * @codeCoverageIgnore
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('format');
        $resolver->setDefault('format', '%s');
        $resolver->setAllowedTypes('format', 'string');
    }
}
