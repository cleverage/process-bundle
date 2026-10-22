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

namespace CleverAge\ProcessBundle\Registry;

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Exception\UnexpectedValueException;
use CleverAge\ProcessBundle\Transformer\TransformerInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Holds the transformers, indexed by code.
 *
 * The transformer services are fetched lazily from a service locator indexed by code (`code` attribute of the
 * `cleverage.transformer` tag, or `#[AsTransformer]`, see TransformerLocatorCompilerPass): a transformer is only
 * instantiated when it is used.
 *
 * @final since 5.4
 */
class TransformerRegistry implements TransformerRegistryInterface
{
    /**
     * Transformers already fetched from the locator, and the ones registered with addTransformer() (deprecated).
     *
     * @var array<string, TransformerInterface>
     */
    protected array $transformers = [];

    /**
     * @param ServiceProviderInterface<TransformerInterface>|null $locator
     */
    public function __construct(
        protected ?ServiceProviderInterface $locator = null,
    ) {
    }

    /**
     * @deprecated since 5.4, tag the transformer service with a "code" attribute or use #[AsTransformer] instead
     */
    public function addTransformer(TransformerInterface $transformer): void
    {
        trigger_deprecation(
            'cleverage/process-bundle',
            '5.4',
            'Registering the transformer "%s" (%s) with "%s()" is deprecated, tag its service with a "code" attribute ({ name: cleverage.transformer, code: %s }) or use the #[AsTransformer] attribute instead, so that it is only instantiated when used. It will be required in v6.0.',
            $transformer->getCode(),
            $transformer::class,
            __METHOD__,
            $transformer->getCode(),
        );
        if ($this->hasTransformer($transformer->getCode())) {
            throw new UnexpectedValueException("Transformer {$transformer->getCode()} is already defined");
        }
        $this->transformers[$transformer->getCode()] = $transformer;
    }

    /**
     * Instantiates every transformer of the locator.
     *
     * @return array<string, TransformerInterface>
     */
    public function getTransformers(): array
    {
        foreach (array_keys($this->locator?->getProvidedServices() ?? []) as $code) {
            $this->getTransformer((string) $code);
        }

        return $this->transformers;
    }

    public function getTransformer(string $code): TransformerInterface
    {
        if (isset($this->transformers[$code])) {
            return $this->transformers[$code];
        }
        if (!$this->locator?->has($code)) {
            throw MissingTransformerException::create($code);
        }

        $transformer = $this->locator->get($code);
        if (!$transformer instanceof TransformerInterface) {
            throw new UnexpectedValueException(\sprintf('The service of the transformer "%s" must implement "%s".', $code, TransformerInterface::class));
        }
        // @phpstan-ignore method.deprecated (the code returned by getCode() must stay the same until v6.0)
        if ($code !== $transformer->getCode()) {
            // @phpstan-ignore method.deprecated (the code returned by getCode() must stay the same until v6.0)
            throw new UnexpectedValueException(\sprintf('The transformer "%s" is registered with the code "%s" but its getCode() method returns "%s": both must be the same.', $transformer::class, $code, $transformer->getCode()));
        }

        return $this->transformers[$code] = $transformer;
    }

    public function hasTransformer(string $code): bool
    {
        return isset($this->transformers[$code]) || ($this->locator?->has($code) ?? false);
    }
}
