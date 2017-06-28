<?php
/*
 *    CleverAge/ProcessBundle
 *    Copyright (C) 2017 Clever-Age
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Transform a value to another value based on a conversion table
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class ConvertValueTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value
     *
     * @param mixed $value
     * @param array $options
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \UnexpectedValueException
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        if (null === $value) {
            return $value;
        }

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $options = $resolver->resolve($options);

        if (!array_key_exists($value, $options['map'])) {
            if (!$options['ignore_missing']) {
                throw new \UnexpectedValueException("Missing value in map '{$value}'");
            }

            return null;
        }

        return $options['map'][$value];
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'convert_value';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'map',
            ]
        );
        $resolver->setAllowedTypes('map', ['array']);
        $resolver->setDefaults([
            'ignore_missing' => false,
        ]);
        $resolver->setAllowedTypes('ignore_missing', ['bool']);
    }
}
