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

use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @todo vclavreul comment this class
 */
class DefaultTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('value');
    }

    /**
     * @param mixed $value
     * @param array $options
     *
     * @return mixed
     */
    public function transform($value, array $options = [])
    {
        if (!$value) {
            return $options['value'];
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return 'default';
    }
}
