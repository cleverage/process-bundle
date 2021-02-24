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
 * Cast a value to a different PHP type
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class CastTransformer implements ConfigurableTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($value, array $options = [])
    {
        settype($value, $options['type']);

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'cast';
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
                'type',
            ]
        );
        $resolver->setAllowedTypes('type', ['string']);
    }
}
