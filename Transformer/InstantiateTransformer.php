<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Instantiate a new object with parameters from the input array
 *
 * @author Vincent Chalnot <vincent.chalnot@quarks.pro>
 */
class InstantiateTransformer implements ConfigurableTransformerInterface
{
    public function transform(mixed $value, array $options = [])
    {
        if (!is_array($value)) {
            throw new \UnexpectedValueException('Input value must be an array for transformer instantiate');
        }

        return (new \ReflectionClass($options['class']))->newInstanceArgs($value);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'class',
            ]
        );
        $resolver->setAllowedTypes('class', ['string']);
    }

    public function getCode()
    {
        return 'instantiate';
    }
}
