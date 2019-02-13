<?php
/**
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Transformer\Cache;


use CleverAge\ProcessBundle\Tests\AbstractProcessTest;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Assert the correct behavior of cache deleter
 */
class DeleterTransformerTest extends AbstractProcessTest
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

            $cacheItem = $this->cache->getItem('DeleterTransformerTest_testDeleteExistingCache');
            $cacheItem->set([]);
            $this->cache->save($cacheItem);

            $result = $this->processManager->execute('test.cache_deleter_transformer.delete_existing_cache', $input);
            self::assertEquals($input, $result);

            self::assertFalse($this->cache->hasItem('DeleterTransformerTest_testDeleteExistingCache'));
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

            $result = $this->processManager->execute('test.cache_deleter_transformer.delete_missing_cache', $input);
            self::assertEquals($input, $result);

            self::assertFalse($this->cache->hasItem('DeleterTransformerTest_testDeleteMissingCache'));
        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testTransformCacheKey()
    {
        if ($this->cache) {
            $input = ['DeleterTransformerTest', 'testTransformCacheKey'];

            $cacheItem = $this->cache->getItem('DeleterTransformerTest_testTransformCacheKey');
            $cacheItem->set([]);
            $this->cache->save($cacheItem);

            $result = $this->processManager->execute('test.cache_deleter_transformer.transform_cache_key', $input);
            self::assertEquals($input, $result);

            self::assertFalse($this->cache->hasItem('DeleterTransformerTest_testTransformCacheKey'));

        }
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function testBadCacheKey()
    {
        if ($this->cache) {
            $input = ['DeleterTransformerTest', 'testBadCacheKey'];

            $result = $this->processManager->execute('test.cache_deleter_transformer.bad_cache_key', $input);
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
