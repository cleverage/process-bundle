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
 * Return the nth element of an array
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ArrayElementTransformer implements ConfigurableTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value, array $options = [])
    {
        return array_values(array_slice($value, $options['index'], 1))[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'array_element';
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
                'index',
            ]
        );
        $resolver->setAllowedTypes('index', ['integer']);
    }
}
