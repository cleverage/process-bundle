<?php declare(strict_types=1);
/*
* This file is part of the CleverAge/ProcessBundle package.
*
* Copyright (C) 2017-2019 Clever-Age
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace CleverAge\ProcessBundle\Task\Doctrine;

use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FlushableCache;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Purge Doctrine internal caches, might be useful for long process with tons of queries
 *
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class PurgeDoctrineCacheTask extends AbstractConfigurableTask
{
    /** @var array */
    protected const METHOD_MAP = [
        'query_cache' => 'getQueryCacheImpl',
        'result_cache' => 'getResultCacheImpl',
        'hydration_cache' => 'getHydrationCacheImpl',
        'metadata_cache' => 'getMetadataCacheImpl',
    ];

    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param ProcessState $state
     */
    public function execute(ProcessState $state)
    {
        $entityManager = $this->getOption($state, 'entity_manager');
        if ($entityManager) {
            $this->purgeEntityManagerCache($entityManager, $state);
        } else {
            foreach ($this->doctrine->getManagers() as $entityManager) {
                if ($entityManager instanceof EntityManagerInterface) {
                    $this->purgeEntityManagerCache($entityManager, $state);
                }
            }
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param ProcessState           $state
     */
    protected function purgeEntityManagerCache(EntityManagerInterface $entityManager, ProcessState $state): void
    {
        $options = $this->getOptions($state);
        foreach (self::METHOD_MAP as $option => $method) {
            if ($options[$option]) {
                $this->purgeCache($entityManager->getConfiguration()->$method());
            }
        }
    }

    /**
     * @param Cache $cache
     */
    protected function purgeCache(Cache $cache = null): void
    {
        if ($cache instanceof FlushableCache) {
            $cache->flushAll();
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'query_cache' => true,
                'result_cache' => true,
                'hydration_cache' => true,
                'metadata_cache' => false,
                'entity_manager' => null, // Purge all entity managers by default
            ]
        );
        $resolver->setAllowedTypes('query_cache', ['bool']);
        $resolver->setAllowedTypes('result_cache', ['bool']);
        $resolver->setAllowedTypes('hydration_cache', ['bool']);
        $resolver->setAllowedTypes('entity_manager', ['NULL', 'string', EntityManagerInterface::class]);
        $resolver->setNormalizer(
            'entity_manager',
            function (
                /** @noinspection PhpUnusedParameterInspection */
                Options $options,
                $value
            ) {
                if (null === $value) {
                    return null;
                }
                if (is_string($value)) {
                    $value = $this->doctrine->getManager($value);
                }
                if (!$value instanceof EntityManagerInterface) {
                    throw new \UnexpectedValueException('Unable to resolve entity manager');
                }

                return $value;
            }
        );
    }
}
