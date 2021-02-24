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

use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PregFilterTransformer
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class PregFilterTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value
     *
     * @param mixed $value
     * @param array $options
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        $pattern = $options['pattern'];
        $replacement = $options['replacement'];

        return preg_filter($pattern, $replacement, $value);
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'preg_filter';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'pattern',
                'replacement',
            ]
        );
        $resolver->setAllowedTypes('pattern', ['string', 'array']);
        $resolver->setAllowedTypes('replacement', ['string', 'array']);
    }
}
