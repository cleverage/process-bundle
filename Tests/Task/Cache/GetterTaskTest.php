<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Task\Cache;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Assert the correct behavior of cache getter
 */
class GetterTaskTest extends AbstractProcessTest
{
    /** @var CacheItemPoolInterface|null */
    protected $cache;

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetExistingCache()
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

            $cacheItem = $this->cache->getItem('GetterTaskTest_testGetExistingCache');
            $cacheItem->set($input);
            $this->cache->save($cacheItem);

            $result = $this->processManager->execute('test.cache_getter_task.get_existing_cache');
            self::assertEquals($input, $result);
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testGetMissingCache()
    {
        if ($this->cache) {
            $result = $this->processManager->execute('test.cache_getter_task.get_missing_cache');
            self::assertEquals('missing cache', $result);
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testTransformCacheKey()
    {
        if ($this->cache) {
            $input = ['GetterTaskTest', 'testTransformCacheKey'];

            $cacheItem = $this->cache->getItem('GetterTaskTest_testTransformCacheKey');
            $cacheItem->set($input);
            $this->cache->save($cacheItem);

            $result = $this->processManager->execute('test.cache_getter_task.transform_cache_key', $input);
            self::assertEquals($input, $result);
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testBadCacheKey()
    {
        if ($this->cache) {
            $input = ['GetterTaskTest', 'testBadCacheKey'];

            $cacheItem = $this->cache->getItem('GetterTaskTest_testBadCacheKey');
            $cacheItem->set($input);
            $this->cache->save($cacheItem);

            $result = $this->processManager->execute('test.cache_getter_task.bad_cache_key', $input);
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
