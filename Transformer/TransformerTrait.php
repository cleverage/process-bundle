<?php
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2018 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Factory\InstancedTransformerFactory;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Trait TransformerTrait
 *
 * @package CleverAge\ProcessBundle\Transformer
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
trait TransformerTrait
{

    /** @var TransformerRegistry */
    protected $transformerRegistry;

    /** @var InstancedTransformerFactory */
    protected $instancedTransformerFactory;

    /**
     * @param array $transformers
     * @param mixed $value
     *
     * @throws \CleverAge\ProcessBundle\Exception\TransformerException
     *
     * @return mixed
     */
    protected function applyTransformers(array $transformers, $value)
    {
        /** @noinspection ForeachSourceInspection */
        foreach ($transformers as $transformerCode => $transformer) {
            try {
                if ($transformer instanceof TransformerInterface) {
                    $value = $transformer->transform($value);
                } elseif (is_callable($transformer)) {
                    /** @deprecated TODO remove this part in next major version */
                    $value = $transformer($value);
                } else {
                    throw new \UnexpectedValueException("Transformer {$transformerCode} cannot be executed");
                }
            } catch (\Throwable $exception) {
                throw new TransformerException($transformerCode, 0, $exception);
            }
        }

        return $value;
    }

    /**
     * This allows to use transformer codes suffixes to avoid limitations to the "transformers" option using codes as
     * keys This way you can chain multiple times the same transformer. Without this, it would silently call only the
     * 1st one.
     *
     * @example
     * transformers:
     *     callback#1:
     *         callback: array_filter
     *     callback#2:
     *         callback: array_reverse
     *
     *
     * @param string $transformerCode
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     *
     * @return string
     */
    protected function getCleanedTransfomerCode(string $transformerCode)
    {
        $match = preg_match('/([^#]+)(#[\d]+)?/', $transformerCode, $parts);

        if (1 === $match && $this->transformerRegistry->hasTransformer($parts[1])) {
            return $parts[1];
        }

        return $transformerCode;
    }

    /**
     * @param \Symfony\Component\OptionsResolver\OptionsResolver $resolver
     *
     * @throws \CleverAge\ProcessBundle\Exception\MissingTransformerException
     * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    protected function configureTransformersOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('transformers', []);
        $resolver->setAllowedTypes('transformers', ['array']);
        /** @noinspection PhpUnusedParameterInspection */
        $resolver->setNormalizer( // This logic is duplicated from the array_map transformer @todo fix me
            'transformers',
            function (Options $options, $transformers) {
                $transformerInstances = [];

                /** @var array $transformers */
                foreach ($transformers as $originalTransformerCode => $transformerOptions) {
                    // Default is an empty array
                    if ($transformerOptions === null) {
                        $transformerOptions = [];
                    } elseif (is_callable($transformerOptions) || $transformerOptions instanceof TransformerInterface) {
                        // May happen if configureOptions is called twice... but it's not a good idea...
                        // TODO should throw ?
                        $transformerInstances[$originalTransformerCode] = $transformerOptions;

                        continue;
                    }

                    $transformerCode = $this->getCleanedTransfomerCode($originalTransformerCode);

                    if ($this->instancedTransformerFactory instanceof InstancedTransformerFactory) {
                        $transformerInstances[$originalTransformerCode] = $this->instancedTransformerFactory->create($transformerCode, $transformerOptions);
                    } else {
                        /** @deprecated TODO remove this block in next major version */
                        @trigger_error('Deprecated method (will be dropped), you need to provide instancedTransformerFactory', E_USER_DEPRECATED);

                        $transformer = $this->transformerRegistry->getTransformer($transformerCode);
                        if ($transformer instanceof ConfigurableTransformerInterface) {
                            $transformerOptionsResolver = new OptionsResolver();
                            $transformer->configureOptions($transformerOptionsResolver);
                            $transformerOptions = $transformerOptionsResolver->resolve($transformerOptions);
                        }

                        $transformerInstances[$originalTransformerCode] = function ($value) use ($transformer, $transformerOptions) {
                            return $transformer->transform($value, $transformerOptions);
                        };
                    }
                }

                return $transformerInstances;
            }
        );
    }

}
