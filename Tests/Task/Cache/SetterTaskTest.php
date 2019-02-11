<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task\Cache;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Assert the correct behavior of cache setter
 */
class SetterTaskTest extends AbstractProcessTest
{
    /** @var CacheItemPoolInterface|null */
    protected $cache;

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSetExistingCache()
    {
        if ($this->cache) {
            $input = [
                [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => ['something'],
                ],
                [
                    'key1' => 'value1b',
                    'key2' => 'value2b',
                    'key3' => ['something'],
                ],
                [
                    'key1' => 'value1c',
                    'key2' => 'value2c',
                    'key3' => [],
                ],
            ];

            $cacheItem = $this->cache->getItem('SetterTaskTest_testSetExistingCache');
            $cacheItem->set([]);
            $this->cache->save($cacheItem);

            $this->processManager->execute('test.cache_setter_task.set_existing_cache', $input);

            $resultCacheItem = $this->cache->getItem('SetterTaskTest_testSetExistingCache');
            self::assertEquals($input[0], $resultCacheItem->get());
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testSetMissingCache()
    {
        if ($this->cache) {
            $input = [
                [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => ['something'],
                ],
                [
                    'key1' => 'value1b',
                    'key2' => 'value2b',
                    'key3' => ['something'],
                ],
                [
                    'key1' => 'value1c',
                    'key2' => 'value2c',
                    'key3' => [],
                ],
            ];

            $result = $this->processManager->execute('test.cache_setter_task.set_missing_cache', $input);
            self::assertEquals($input, $result);

            $resultCacheItem = $this->cache->getItem('SetterTaskTest_testSetMissingCache');
            self::assertEquals($input[0], $resultCacheItem->get());
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testTransformCacheKey()
    {
        if ($this->cache) {
            $input = ['SetterTaskTest', 'testTransformCacheKey'];

            $this->processManager->execute('test.cache_setter_task.transform_cache_key', $input);

            $resultCacheItem = $this->cache->getItem('SetterTaskTest_testTransformCacheKey');
            self::assertEquals($input, $resultCacheItem->get());
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testBadCacheKey()
    {
        if ($this->cache) {
            $input = ['SetterTransformerTest', 'testBadCacheKey'];

            $result = $this->processManager->execute('test.cache_setter_task.bad_cache_key', $input);
            self::assertEquals('missing cache', $result);
        }
    }

    protected function setUp()
    {
        parent::setUp();

        if (static::$kernel->getContainer()->has('cache.app')) {
            $this->cache = static::$kernel->getContainer()->get('cache.app');
        }
    }
}
