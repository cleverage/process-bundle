<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Task\Cache;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Model\AbstractConfigurableTask;
use CleverAge\ProcessBundle\Model\ProcessState;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Class AbstractCacheTask
 *
 * @author Madeline Veyrenc <mveyrenc@clever-age.com>
 */
abstract class AbstractCacheTask extends AbstractConfigurableTask
{
    use TransformerTrait;

    /** @var LoggerInterface */
    private $logger;

    /** @var PropertyAccessorInterface */
    private $accessor;

    /** @var CacheItemPoolInterface */
    private $cache;

    /**
     * SetterTask constructor.
     *
     * @param LoggerInterface           $logger
     * @param PropertyAccessorInterface $accessor
     * @param CacheItemPoolInterface    $cache
     * @param TransformerRegistry       $transformerRegistry
     */
    public function __construct(
        LoggerInterface $logger,
        PropertyAccessorInterface $accessor,
        CacheItemPoolInterface $cache,
        TransformerRegistry $transformerRegistry
    ) {
        $this->logger = $logger;
        $this->accessor = $accessor;
        $this->cache = $cache;
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * @return PropertyAccessorInterface
     */
    public function getAccessor(): PropertyAccessorInterface
    {
        return $this->accessor;
    }

    /**
     * @return CacheItemPoolInterface
     */
    public function getCache(): CacheItemPoolInterface
    {
        return $this->cache;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'key',
            ]
        );
        $resolver->setAllowedTypes('key', ['array', 'null']);

        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer(
            'key',
            function (Options $options, $value) {
                $mappingResolver = new OptionsResolver();
                $this->configureMappingOptions($mappingResolver);

                return $mappingResolver->resolve(
                    $value ?? []
                );
            }
        );
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    protected function configureMappingOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'code' => null, // Source property
                'constant' => null,
            ]
        );
        $resolver->setAllowedTypes('code', ['NULL', 'string', 'array']);

        $this->configureTransformersOptions($resolver);
    }

    /**
     * @param ProcessState $state
     *
     * @return string
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     */
    protected function getKeyCache(ProcessState $state)
    {
        $options = $this->getOptions($state);
        $input = $state->getInput();
        $key = $options['key'];
        $keyValue = null;

        if (null !== $key['constant']) {
            $keyValue = $key['constant'];
        } elseif (null !== $key['code']) {
            $sourceProperty = $key['code'];
            if (\is_array($sourceProperty)) {
                $keyValue = [];
                /** @var array $sourceProperty */
                foreach ($sourceProperty as $destKey => $srcKey) {
                    try {
                        $keyValue[$destKey] = $this->getAccessor()->getValue($input, $srcKey);
                    } catch (\RuntimeException $missingPropertyError) {
                        $this->getLogger()->debug(
                            'Mapping exception',
                            [
                                'srcKey' => $srcKey,
                                'message' => $missingPropertyError->getMessage(),
                            ]
                        );
                        throw $missingPropertyError;
                    }
                }
            } else {
                try {
                    $keyValue = $this->getAccessor()->getValue($input, $sourceProperty);
                } catch (\RuntimeException $missingPropertyError) {
                    $this->getLogger()->debug(
                        'Mapping exception',
                        [
                            'message' => $missingPropertyError->getMessage(),
                        ]
                    );
                    throw $missingPropertyError;
                }
            }
        } else {
            $keyValue = $input;
        }

        try {
            $keyValue = $this->applyTransformers($key['transformers'], $keyValue);
        } catch (TransformerException $exception) {
            $exception->setTargetProperty('key');
            $this->logger->debug(
                'Transformation exception',
                [
                    'message' => $exception->getPrevious()->getMessage(),
                    'file' => $exception->getPrevious()->getFile(),
                    'line' => $exception->getPrevious()->getLine(),
                    'trace' => $exception->getPrevious()->getTraceAsString(),
                ]
            );

            throw $exception;
        }

        return $keyValue;
    }
}
