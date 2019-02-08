<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer\Cache;

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
        $cacheItem->set($value);
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
}
