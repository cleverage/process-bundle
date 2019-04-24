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

/**
 * Class DeleterTransformer
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
class DeleterTransformer extends AbstractCacheTransformer
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

        $this->getCache()->deleteItem($keyValue);

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getCode()
    {
        return 'cache_deleter';
    }
}
