<?php declare(strict_types=1);
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
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CachedTransformer implements ConfigurableTransformerInterface
{

    const CACHE_SEPARATOR = '|';

    use TransformerTrait;

    /** @var CacheItemPoolInterface */
    protected $cache;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * CachedTransformer constructor.
     *
     * @param TransformerRegistry    $transformerRegistry
     * @param CacheItemPoolInterface $cache
     * @param LoggerInterface        $logger
     */
    public function __construct(
        TransformerRegistry $transformerRegistry,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        $this->transformerRegistry = $transformerRegistry;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('cache_key');
        $resolver->setAllowedTypes('cache_key', 'string');

        $resolver->setDefault('ttl', null);
        $resolver->setAllowedTypes('ttl', ['null', 'string', \DateTimeInterface::class]);
        $resolver->setNormalizer(
            'ttl',
            function (Options $options, $value) {
                /**
                 * Best use is a relative date string like "+1 hour"
                 * @see https://www.php.net/manual/en/datetime.formats.relative.php
                 */
                if (is_string($value)) {
                    $value = new \DateTime($value);
                }

                return $value;
            }
        );

        $this->configureTransformersOptions($resolver);
        $this->configureTransformersOptions($resolver, 'key_transformers');
    }

    public function transform($value, array $options = [])
    {
        $cacheKey = $this->generateCacheKey($options['cache_key'], $value, $options);
        if ($cacheKey && $this->cache instanceof CacheItemPoolInterface) {
            try {
                $cacheItem = $this->cache->getItem($cacheKey);
                if ($cacheItem->isHit()) {
                    return $cacheItem->get();
                } else {
                    $newValue = $this->applyTransformers($options['transformers'], $value);
                    $cacheItem->set($newValue);
                    if ($options['ttl']) {
                        $cacheItem->expiresAt($options['ttl']);
                    }
                    $success = $this->cache->saveDeferred($cacheItem);

                    if (!$success) {
                        $this->logger->warning('Cannot save cache item', ['cache_key' => $cacheKey]);
                    }

                    return $newValue;
                }
            } catch (InvalidArgumentException $exception) {
                $this->logger->warning(
                    'Cannot get cache item',
                    ['cache_key' => $cacheKey, 'message' => $exception->getMessage()]
                );
            }
        }

        return $this->applyTransformers($options['transformers'], $value);
    }

    public function getCode()
    {
        return 'cached';
    }

    protected function generateCacheKey($cacheKeyRoot, $value, $options)
    {
        $value = $this->applyTransformers($options['key_transformers'], $value);

        if (!\is_string($value)) {
            return false;
        }

        return \implode(self::CACHE_SEPARATOR, [$cacheKeyRoot, \rawurlencode($value)]);
    }

}
