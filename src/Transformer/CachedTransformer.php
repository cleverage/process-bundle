<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2023 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use DateTime;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CachedTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    final public const CACHE_SEPARATOR = '|';

    public function __construct(
        TransformerRegistry $transformerRegistry,
        protected CacheItemPoolInterface $cache,
        protected LoggerInterface $logger
    ) {
        $this->transformerRegistry = $transformerRegistry;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('cache_key');
        $resolver->setAllowedTypes('cache_key', 'string');

        $resolver->setDefault('ttl', null);
        $resolver->setAllowedTypes('ttl', ['null', 'string', \DateTimeInterface::class]);
        $resolver->setNormalizer(
            'ttl',
            function (Options $options, $value) {
                /*
                 * Best use is a relative date string like "+1 hour".
                 *
                 * @see https://www.php.net/manual/en/datetime.formats.relative.php
                 */
                if (\is_string($value)) {
                    $value = new \DateTime($value);
                }

                return $value;
            }
        );

        $this->configureTransformersOptions($resolver);
        $this->configureTransformersOptions($resolver, 'key_transformers');
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        $cacheKey = $this->generateCacheKey($options['cache_key'], $value, $options);
        if ($cacheKey && $this->cache instanceof CacheItemPoolInterface) {
            try {
                $cacheItem = $this->cache->getItem($cacheKey);
                if ($cacheItem->isHit()) {
                    return $cacheItem->get();
                }
                $newValue = $this->applyTransformers($options['transformers'], $value);
                $cacheItem->set($newValue);
                if ($options['ttl']) {
                    $cacheItem->expiresAt($options['ttl']);
                }
                $success = $this->cache->saveDeferred($cacheItem);

                if (!$success) {
                    $this->logger->warning('Cannot save cache item', [
                        'cache_key' => $cacheKey,
                    ]);
                }

                return $newValue;
            } catch (InvalidArgumentException $exception) {
                $this->logger->warning(
                    'Cannot get cache item',
                    [
                        'cache_key' => $cacheKey,
                        'message' => $exception->getMessage(),
                    ]
                );
            }
        }

        return $this->applyTransformers($options['transformers'], $value);
    }

    public function getCode(): string
    {
        return 'cached';
    }

    protected function generateCacheKey(string $cacheKeyRoot, string $value, array $options): bool|string
    {
        $value = $this->applyTransformers($options['key_transformers'], $value);

        if (!\is_string($value)) {
            return false;
        }

        return implode(self::CACHE_SEPARATOR, [$cacheKeyRoot, rawurlencode($value)]);
    }
}
