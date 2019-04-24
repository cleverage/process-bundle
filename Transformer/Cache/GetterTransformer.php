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

use CleverAge\ProcessBundle\Exception\TransformerException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class GetterTransformer
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class GetterTransformer extends AbstractCacheTransformer
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults(
            [
                'ignore_not_hit' => false,
            ]
        );
        $resolver->setAllowedTypes('ignore_not_hit', ['boolean']);
    }

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

        if (!$cacheItem->isHit()) {
            if ($options['ignore_not_hit']) {
                return null;
            }

            throw new TransformerException($keyValue, 0, 'Cache not hit');
        }

        return $cacheItem->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'cache_getter';
    }
}
