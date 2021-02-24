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
 * Return always the same value configured in options, redundant when used inside mapping except in certain useful
 * circumstances
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ConstantTransformer implements ConfigurableTransformerInterface
{
    /**
     * @param OptionsResolver $resolver
     *
     * @throws ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'constant',
            ]
        );
    }

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
        return $options['constant'] ?? null;
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode(): string
    {
        return 'constant';
    }
}
