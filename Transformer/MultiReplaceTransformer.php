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
 * Quickly replace a list of values in a string
 *
 * ##### Options
 *
 * * `replace_mapping` (`array`, _required_): a list of _pattern_ => _replacement_ to apply on input strings
 */
class MultiReplaceTransformer implements ConfigurableTransformerInterface
{
    public function transform($value, array $options = [])
    {
        foreach($options['replace_mapping'] as $pattern => $replacement) {
            $value = str_replace($pattern, $replacement, $value);
        }

        return $value;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('replace_mapping');
        $resolver->setAllowedTypes('replace_mapping', 'array');
    }

    public function getCode()
    {
        return 'multi_replace';
    }

}
