<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer\Cache;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SetterTransformer
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class SetterTransformer extends AbstractCacheTransformer
{
    /**
     * {@inheritDoc}
     *
     * @throws \UnexpectedValueException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function transform($value, array $options = [])
    {
        $keyValue = $this->getKeyCache($value, $options);

        $cacheItem = $this->getCache()->getItem($keyValue);
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);
        $cachedValue = $this->transformValue($value, $options['value']);
        $cacheItem->set($cachedValue);
        $this->getCache()->save($cacheItem);

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'cache_setter';
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setRequired(
            [
                'value',
            ]
        );
        $resolver->setAllowedTypes('value', ['array', 'null']);

        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'value',
            function (Options $options, $value) {
                $mappingResolver = new OptionsResolver();
                $this->configureMappingOptions($mappingResolver);

                return $mappingResolver->resolve(
                    $value ?? []
                );
            }
        );

        return $resolver;
    }


}
