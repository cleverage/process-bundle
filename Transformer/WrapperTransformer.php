<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class WrapperTransformer
 *
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class WrapperTransformer implements ConfigurableTransformerInterface
{

    /**
     * Must return the transformed $value
     *
     * @param mixed $input
     * @param array $options
     *
     * @return mixed $value
     */
    public function transform($input, array $options = [])
    {
        return [$options['wrapper_key'] => $input];
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
                'wrapper_key',
            ]
        );
        $resolver->setAllowedTypes('wrapper_key', ['string']);
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'wrapper';
    }
}
