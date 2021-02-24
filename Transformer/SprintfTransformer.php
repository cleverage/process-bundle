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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Use sprintf() function to format string
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 * @author Corentin Bouix <cbouix@clever-age.com>
 */
class SprintfTransformer implements ConfigurableTransformerInterface
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('format');
        $resolver->setDefault('format', '%s');
        $resolver->setAllowedTypes('format', 'string');
    }

    /**
     * {@inheritDoc}
     * @throws \UnexpectedValueException
     */
    public function transform($value, array $options = [])
    {
        if (!\is_array($value)) {
            $value = [$value];
        }

        return vsprintf($options['format'], $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'sprintf';
    }
}
