<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task\Cache;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Assert the correct behavior of cache deleter
 */
class DeleterTaskTest extends AbstractProcessTest
{
    /** @var CacheItemPoolInterface|null */
    protected $cache;

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDeleteExistingCache()
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

            $cacheItem = $this->cache->getItem('DeleterTaskTest_testDeleteExistingCache');
            $cacheItem->set([]);
            $this->cache->save($cacheItem);

            $this->processManager->execute('test.cache_deleter_task.delete_existing_cache', $input);

            self::assertFalse($this->cache->hasItem('DeleterTaskTest_testDeleteExistingCache'));
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testDeleteMissingCache()
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

            $this->processManager->execute('test.cache_deleter_task.delete_missing_cache', $input);

            self::assertFalse($this->cache->hasItem('DeleterTaskTest_testDeleteMissingCache'));
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testTransformCacheKey()
    {
        if ($this->cache) {
            $input = ['DeleterTaskTest', 'testTransformCacheKey'];

            $cacheItem = $this->cache->getItem('DeleterTaskTest_testTransformCacheKey');
            $cacheItem->set([]);
            $this->cache->save($cacheItem);

            $this->processManager->execute('test.cache_deleter_task.transform_cache_key', $input);

            self::assertFalse($this->cache->hasItem('DeleterTaskTest_testTransformCacheKey'));

        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testBadCacheKey()
    {
        if ($this->cache) {
            $input = ['DeleterTaskTest', 'testBadCacheKey'];

            $result = $this->processManager->execute('test.cache_deleter_task.bad_cache_key', $input);
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
