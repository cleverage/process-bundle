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

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\NoSuchOptionException;
use Symfony\Component\OptionsResolver\Exception\OptionDefinitionException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Maps properties of an array/object to an other array/object
 *
 * @author Valentin Clavreul <vclavreul@clever-age.com>
 * @author Vincent Chalnot <vchalnot@clever-age.com>
 */
class MappingTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    /** @var LoggerInterface */
    protected $logger;

    /** @var PropertyAccessorInterface */
    protected $accessor;

    /**
     * @param TransformerRegistry       $transformerRegistry
     * @param LoggerInterface           $logger
     * @param PropertyAccessorInterface $accessor
     */
    public function __construct(
        TransformerRegistry $transformerRegistry,
        LoggerInterface $logger,
        PropertyAccessorInterface $accessor
    ) {
        $this->transformerRegistry = $transformerRegistry;
        $this->logger = $logger;
        $this->accessor = $accessor;
    }

    /**
     * Must return the transformed $value
     *
     * @param mixed $input
     * @param array $options
     *
     * @return mixed $value
     * @throws \Exception
     *
     */
    public function transform($input, array $options = [])
    {
        if (!empty($options['initial_value']) && $options['keep_input']) {
            throw new InvalidOptionsException(
                'The options "initial_value" and "keep_input" can\'t be both enabled.'
            );
        }

        $result = $options['initial_value'];
        if ($options['keep_input']) {
            $result = $input;
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($options['mapping'] as $targetProperty => $mapping) {
            $targetProperty = (string) $targetProperty;
            $sourceProperty = $mapping['code'] ?? $targetProperty;
            $ignoreMissingFlag = $mapping['ignore_missing'] || $options['ignore_missing'];

            // Prepare input value
            if (null !== $mapping['constant']) {
                $inputValue = $mapping['constant'];
            } elseif ($mapping['set_null']) {
                $inputValue = null;
            } elseif (\is_array($sourceProperty)) {
                $inputValue = [];
                /** @var array $sourceProperty */
                foreach ($sourceProperty as $destKey => $srcKey) {
                    try {
                        $inputValue[$destKey] = $this->extractInputValue($input, $srcKey);
                    } catch (RuntimeException $missingPropertyError) {
                        $this->handleInputMissingExceptions($missingPropertyError, $srcKey);
                        if ($ignoreMissingFlag) {
                            continue;
                        }
                        throw $missingPropertyError;
                    }
                }
            } else {
                try {
                    $inputValue = $this->extractInputValue($input, $sourceProperty);
                } catch (RuntimeException $missingPropertyError) {
                    $this->handleInputMissingExceptions($missingPropertyError, $sourceProperty);
                    if ($ignoreMissingFlag) {
                        continue;
                    }
                    throw $missingPropertyError;
                }
            }

            // Transform input value
            try {
                $transformedValue = $this->applyTransformers($mapping['transformers'], $inputValue);
            } catch (TransformerException $exception) {
                $exception->setTargetProperty($targetProperty);
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

            // Set transformed value into result
            if (\is_callable($options['merge_callback'])) {
                $options['merge_callback']($result, $targetProperty, $transformedValue);
            } elseif ($this->accessor->isWritable($result, $targetProperty)) {
                $this->accessor->setValue($result, $targetProperty, $transformedValue);
            } elseif (\is_array($result)) {
                $result[$targetProperty] = $transformedValue;
            } else {
                throw new \UnexpectedValueException("Property '{$targetProperty}' is not writable");
            }
        }

        return $result;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws OptionDefinitionException
     * @throws NoSuchOptionException
     * @throws MissingOptionsException
     * @throws InvalidOptionsException
     * @throws UndefinedOptionsException
     * @throws AccessException
     * @throws MissingTransformerException
     * @throws ExceptionInterface
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'mapping',
            ]
        );
        $resolver->setAllowedTypes('mapping', ['array']);
        $resolver->setDefaults(
            [
                'ignore_missing' => false,
                'keep_input' => false,
                'initial_value' => [],
                'merge_callback' => null,
            ]
        );
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);
        $resolver->setAllowedTypes('keep_input', ['boolean']);
        $resolver->setAllowedTypes('merge_callback', ['null', 'callable']);

        $resolver->setNormalizer(
            'mapping',
            function (/** @noinspection PhpUnusedParameterInspection */ Options $options, $value) {
                $resolvedMapping = [];
                $mappingResolver = new OptionsResolver();
                $this->configureMappingOptions($mappingResolver);
                /** @var array $value */
                foreach ($value as $property => $mappingConfig) {
                    $resolvedMapping[$property] = $mappingResolver->resolve(
                        $mappingConfig ?? []
                    );
                }

                return $resolvedMapping;
            }
        );
    }

    /**
     * Returns the unique code to identify the transformer
     *
     * @return string
     */
    public function getCode()
    {
        return 'mapping';
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws OptionDefinitionException
     * @throws NoSuchOptionException
     * @throws MissingOptionsException
     * @throws InvalidOptionsException
     * @throws UndefinedOptionsException
     * @throws AccessException
     * @throws MissingTransformerException
     * @throws ExceptionInterface
     */
    protected function configureMappingOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'code' => null, // Source property
                'constant' => null,
                'set_null' => false, // Because the "null" value cannot be covered by the constant option
                'ignore_missing' => false,
            ]
        );
        $resolver->setAllowedTypes('code', ['null', 'string', 'array']);
        $resolver->setAllowedTypes('set_null', ['boolean']);
        $resolver->setAllowedTypes('ignore_missing', ['boolean']);

        $this->configureTransformersOptions($resolver);
    }

    /**
     * Custom rules to get a value from an input object or array
     *
     * @param mixed  $input
     * @param string $sourceProperty
     *
     * @throws RuntimeException
     *
     * @return mixed
     */
    protected function extractInputValue($input, string $sourceProperty)
    {
        if ($sourceProperty === '.') {
            return $input;
        }

        return $this->accessor->getValue($input, $sourceProperty);
    }

    /**
     * Wrap error handling when there is an property access error
     *
     * @TODO WARNING there is no error if framework.property_access.throw_exception_on_invalid_index is false (which is
     *       the default)
     *
     * @param RuntimeException $missingPropertyError
     * @param string           $srcKey
     */
    protected function handleInputMissingExceptions(RuntimeException $missingPropertyError, string $srcKey)
    {
        $this->logger->debug(
            'Mapping exception',
            [
                'srcKey' => $srcKey,
                'message' => $missingPropertyError->getMessage(),
            ]
        );
    }
}
