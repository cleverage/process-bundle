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
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
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
        $registry = new TransformerRegistry(new ServiceLocator([
            'cast' => static fn (): CastTransformer => new CastTransformer(),
        ]));
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

    /**
     * On a miss, the transformers are applied once, and the value is cached until the ttl.
     */
    public function testTtl(): void
    {
        $counter = new class implements TransformerInterface {
            public int $calls = 0;

            public function transform(mixed $value, array $options = []): mixed
            {
                ++$this->calls;

                return $value.'!';
            }

            public function getCode(): string
            {
                return 'count';
            }
        };
        $registry = new TransformerRegistry(new ServiceLocator(['count' => static fn (): TransformerInterface => $counter]));
        $transformer = new CachedTransformer($registry, $this->cache, new NullLogger());
        $resolve = static function (array $options) use ($transformer): array {
            $resolver = new OptionsResolver();
            $transformer->configureOptions($resolver);

            return $resolver->resolve($options + ['cache_key' => 'prefix', 'transformers' => ['count' => null]]);
        };

        self::assertSame('a!', $transformer->transform('a', $resolve([])));
        self::assertSame(1, $counter->calls);
        self::assertSame('a!', $transformer->transform('a', $resolve([])));
        self::assertSame(1, $counter->calls, 'From the cache');

        // Already expired: not cached
        self::assertSame('b!', $transformer->transform('b', $resolve(['ttl' => '-1 second'])));
        self::assertSame('b!', $transformer->transform('b', $resolve(['ttl' => new \DateTimeImmutable('-1 second')])));
        self::assertSame(3, $counter->calls);

        self::assertSame('c!', $transformer->transform('c', $resolve(['ttl' => '+1 hour'])));
        self::assertSame('c!', $transformer->transform('c', $resolve(['ttl' => '+1 hour'])));
        self::assertSame(4, $counter->calls);
    }

    public function testTtlIsNormalized(): void
    {
        $ttl = $this->resolveOptions(['cache_key' => 'prefix', 'ttl' => '+1 hour'])['ttl'];
        self::assertInstanceOf(\DateTime::class, $ttl);
        self::assertEqualsWithDelta(time() + 3600, $ttl->getTimestamp(), 5);

        $dateTime = new \DateTimeImmutable();
        self::assertSame($dateTime, $this->resolveOptions(['cache_key' => 'prefix', 'ttl' => $dateTime])['ttl']);
        self::assertNull($this->resolveOptions(['cache_key' => 'prefix'])['ttl']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function provideInvalidOptions(): iterable
    {
        yield 'cache_key not a string' => [['cache_key' => 1]];
        yield 'ttl not a string nor a date' => [['cache_key' => 'prefix', 'ttl' => 3600]];
    }

    /**
     * @param array<string, mixed> $options
     */
    #[DataProvider('provideInvalidOptions')]
    public function testInvalidOptions(array $options): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($options);
    }

    public function testFailureToSaveIsLogged(): void
    {
        $item = $this->createStub(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);
        $item->method('set')->willReturnSelf();
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->method('getItem')->willReturn($item);
        $cache->expects(self::once())->method('saveDeferred')->with($item)->willReturn(false);
        $logs = new TestHandler();
        $transformer = new CachedTransformer($this->createStub(TransformerRegistry::class), $cache, new Logger('test', [$logs]));

        self::assertSame('a', $transformer->transform('a', ['cache_key' => 'prefix', 'transformers' => [], 'key_transformers' => [], 'ttl' => null]));
        self::assertTrue($logs->hasWarning(['message' => 'Cannot save cache item', 'context' => ['cache_key' => 'prefix|a']]));
    }

    /**
     * A key value which is not a string: the cache is not used.
     */
    public function testNonStringKeyValueDoesNotUseTheCache(): void
    {
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cache->expects(self::never())->method('getItem');
        $transformer = new CachedTransformer($this->createStub(TransformerRegistry::class), $cache, new NullLogger());

        self::assertSame(42, $transformer->transform(42, ['cache_key' => 'prefix', 'transformers' => [], 'key_transformers' => [], 'ttl' => null]));
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
