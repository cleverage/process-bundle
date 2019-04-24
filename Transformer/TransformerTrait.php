<?php declare(strict_types=1);
/*
 * This file is part of the CleverAge/ProcessBundle package.
 *
 * Copyright (C) 2017-2019 Clever-Age
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author  Madeline Veyrenc <mveyrenc@clever-age.com>
 */
trait TransformerTrait
{
    /** @var TransformerRegistry */
    protected $transformerRegistry;

    /**
     * @param array $transformers
     * @param mixed $value
     *
     * @throws TransformerException
     *
     * @return mixed
     */
    protected function applyTransformers(array $transformers, $value)
    {
        // Quick return for better perfs
        if (empty($transformers)) {
            return $value;
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($transformers as $transformerCode => $transformerClosure) {
            try {
                $value = $transformerClosure($value);
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
     * @param string $transformerCode
     *
     * @throws MissingTransformerException
     *
     * @return string
     *
     * @example
     *         transformers:
     *         callback#1:
     *         callback: array_filter
     *         callback#2:
     *         callback: array_reverse
     *
     *
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
     * @param OptionsResolver $resolver
     * @param string          $optionName
     */
    protected function configureTransformersOptions(OptionsResolver $resolver, $optionName = 'transformers')
    {
        $resolver->setDefault($optionName, []);
        $resolver->setAllowedTypes($optionName, ['array']);
        $resolver->setNormalizer(
            $optionName,
            function (
                /** @noinspection PhpUnusedParameterInspection */
                Options $options,
                $transformers
            ) {
                $transformerClosures = [];

                foreach ($transformers as $origTransformerCode => $transformerOptions) {
                    $transformerOptionsResolver = new OptionsResolver();
                    $transformerCode = $this->getCleanedTransfomerCode($origTransformerCode);
                    $transformer = $this->transformerRegistry->getTransformer($transformerCode);
                    if ($transformer instanceof ConfigurableTransformerInterface) {
                        $transformer->configureOptions($transformerOptionsResolver);
                        $transformerOptions = $transformerOptionsResolver->resolve(
                            $transformerOptions ?? []
                        );
                    }

                    $closure = static function ($value) use ($transformer, $transformerOptions) {
                        return $transformer->transform($value, $transformerOptions);
                    };
                    $transformerClosures[$origTransformerCode] = $closure;
                }

                return $transformerClosures;
            }
        );
    }
}
