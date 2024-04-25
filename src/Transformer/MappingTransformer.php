<?php

declare(strict_types=1);

/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (c) 2017-2024 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\RuntimeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Maps properties of an array/object to an other array/object.
 */
class MappingTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    public function __construct(
        TransformerRegistry $transformerRegistry,
        protected LoggerInterface $logger,
        protected PropertyAccessorInterface $accessor
    ) {
        $this->transformerRegistry = $transformerRegistry;
    }

    public function transform(mixed $value, array $options = []): mixed
    {
        if (!empty($options['initial_value']) && $options['keep_input']) {
            throw new InvalidOptionsException('The options "initial_value" and "keep_input" can\'t be both enabled.');
        }

        $result = $options['initial_value'];
        if ($options['keep_input']) {
            $result = $value;
        }

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
                foreach ($sourceProperty as $destKey => $srcKey) {
                    try {
                        $inputValue[$destKey] = $this->extractInputValue($value, $srcKey);
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
                    $inputValue = $this->extractInputValue($value, $sourceProperty);
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
                        'message' => $exception->getPrevious()
                            ?->getMessage(),
                        'file' => $exception->getPrevious()
                            ?->getFile(),
                        'line' => $exception->getPrevious()
                            ?->getLine(),
                        'trace' => $exception->getPrevious()
                            ?->getTraceAsString(),
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['mapping']);
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
            function (Options $options, $value): array {
                $resolvedMapping = [];
                $mappingResolver = new OptionsResolver();
                $this->configureMappingOptions($mappingResolver);
                /** @var array $value */
                foreach ($value as $property => $mappingConfig) {
                    $resolvedMapping[$property] = $mappingResolver->resolve($mappingConfig ?? []);
                }

                return $resolvedMapping;
            }
        );
    }

    /**
     * Returns the unique code to identify the transformer.
     */
    public function getCode(): string
    {
        return 'mapping';
    }

    protected function configureMappingOptions(OptionsResolver $resolver): void
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
     * Custom rules to get a value from an input object or array.
     */
    protected function extractInputValue(mixed $input, string $sourceProperty): mixed
    {
        if ('.' === $sourceProperty) {
            return $input;
        }

        return $this->accessor->getValue($input, $sourceProperty);
    }

    /**
     * Wrap error handling when there is an property access error.
     *
     * @TODO WARNING there is no error if framework.property_access.throw_exception_on_invalid_index is false (which is
     *       the default)
     */
    protected function handleInputMissingExceptions(RuntimeException $missingPropertyError, string $srcKey): void
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
