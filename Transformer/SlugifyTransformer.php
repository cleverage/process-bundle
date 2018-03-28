<?php
 /*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Slugify a value
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class SlugifyTransformer implements ConfigurableTransformerInterface
{
    /**
     * Must return the transformed $value
     *
     * @param mixed $value
     * @param array $options
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     *
     * @return mixed $value
     */
    public function transform($value, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $transliterator = \Transliterator::create($options['transliterator']);
        $string = $transliterator->transliterate($value);

        return trim(
            preg_replace(
                $options['replace'],
                $options['separator'],
                strtolower(trim(strip_tags($string)))
            ),
            $options['separator']
        );
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'slugify';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'transliterator' => 'NFD; [:Nonspacing Mark:] Remove; NFC',
                'replace' => '/[^a-z0-9]+/',
                'separator' => '_',
            ]
        );
    }
}
