<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\CachedTransformer;
use CleverAge\ProcessBundle\Transformer\CastTransformer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(CachedTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CastTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(CachedTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(CachedTransformer::class, 'generateCacheKey')]
#[\PHPUnit\Framework\Attributes\CoversMethod(CachedTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(CachedTransformer::class, 'getCode')]
class CachedTransformerTest extends TestCase
{
    private ArrayAdapter $cache;

    private CachedTransformer $transformer;

    protected function setUp(): void
    {
        $registry = new TransformerRegistry();
        $registry->addTransformer(new CastTransformer());
        $this->cache = new ArrayAdapter();
        $this->transformer = new CachedTransformer($registry, $this->cache, new NullLogger());
    }

    public function testTransformStringValueIsCached(): void
    {
        $options = $this->resolveOptions(['cache_key' => 'prefix']);

        $this->assertSame('foo bar', $this->transformer->transform('foo bar', $options));
        $this->cache->commit();

        $item = $this->cache->getItem('prefix|foo%20bar');
        $this->assertTrue($item->isHit());
        $this->assertSame('foo bar', $item->get());
    }

    public function testTransformReturnsCachedValueOnHit(): void
    {
        $this->cache->save($this->cache->getItem('prefix|foo')->set('from cache'));
        $options = $this->resolveOptions(['cache_key' => 'prefix']);

        $this->assertSame('from cache', $this->transformer->transform('foo', $options));
    }

    public function testTransformNonStringValueWithKeyTransformers(): void
    {
        $options = $this->resolveOptions([
            'cache_key' => 'prefix',
            'key_transformers' => ['cast' => ['type' => 'string']],
            'transformers' => ['cast' => ['type' => 'float']],
        ]);

        $this->assertSame(42.0, $this->transformer->transform(42, $options));
        $this->cache->commit();

        $item = $this->cache->getItem('prefix|42');
        $this->assertTrue($item->isHit());
        $this->assertSame(42.0, $item->get());
    }

    public function testTransformNonStringKeyValueBypassesCache(): void
    {
        $options = $this->resolveOptions([
            'cache_key' => 'prefix',
            'transformers' => ['cast' => ['type' => 'string']],
        ]);

        $this->assertSame('42', $this->transformer->transform(42, $options));
        $this->cache->commit();

        $this->assertSame([], $this->cache->getValues());
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $this->assertSame('cached', $this->transformer->getCode());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $this->transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
