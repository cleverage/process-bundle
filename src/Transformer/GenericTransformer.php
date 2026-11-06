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

namespace CleverAge\ProcessBundle\Transformer;

use CleverAge\ProcessBundle\Configuration\DeprecatedCodeAliases;
use CleverAge\ProcessBundle\Context\ContextualOptionResolver;
use CleverAge\ProcessBundle\Exception\InvalidArgumentException;
use CleverAge\ProcessBundle\Exception\LogicException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A generic class that can be used to create configuration-driven transformer instances.
 *
 * @final since 5.4
 */
class GenericTransformer implements ConfigurableTransformerInterface
{
    use TransformerTrait;

    protected ?string $transformerCode = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $preconfiguredTransformerOptions = null;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $contextualOptions = null;

    /**
     * Former name (with underscores instead of dashes, before 5.9) of the contextual options whose code has a dash.
     *
     * Deprecated since 5.10, the former names will not be accepted in v6.0.
     *
     * @var array<string, string> former name => code
     */
    private array $deprecatedContextualOptions = [];

    public function __construct(
        protected ContextualOptionResolver $contextualOptionResolver,
        TransformerRegistry $transformerRegistry,
    ) {
        $this->transformerRegistry = $transformerRegistry;
    }

    /**
     * Register the generic options, and load the transformer list.
     *
     * @param array<string, mixed> $options
     */
    public function initialize(string $code, array $options = []): void
    {
        $this->transformerCode = $code;
        $resolver = new OptionsResolver();
        $this->configureInitialOptions($resolver);

        $initialOptions = $resolver->resolve($options);
        $this->contextualOptions = $initialOptions['contextual_options'];
        $this->deprecatedContextualOptions = DeprecatedCodeAliases::create(array_keys($this->contextualOptions));
        $transformers = $initialOptions['transformers'];
        if (\is_array($transformers)) {
            $transformers = $this->replaceFormerTransformerCodes($transformers);
            $this->checkFormerContextualOptionPlaceholders($transformers);
        }
        $this->preconfiguredTransformerOptions = $transformers;
    }

    /**
     * Called on instance creation.
     */
    public function configureInitialOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('contextual_options', []);
        $resolver->setAllowedTypes('contextual_options', 'array');
        $resolver->setNormalizer('contextual_options', function (Options $options, $value): array {
            $configuration = [];
            foreach ($value as $optionCode => $optionConfig) {
                $resolver = new OptionsResolver();
                $this->configureContextualOptions($resolver);
                $configuration[$optionCode] = $resolver->resolve($optionConfig ?? []);
            }

            return $configuration;
        });

        $resolver->setDefault('transformers', []);
    }

    /**
     * Called on process startup, prepare the real transformers.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        foreach ($this->contextualOptions ?? [] as $option => $optionConfig) {
            $formerName = array_search($option, $this->deprecatedContextualOptions, true);
            if (false !== $formerName) {
                $this->configureFormerContextualOption($resolver, $option, $formerName, $optionConfig);
                continue;
            }
            $resolver->setDefined($option);
            if (null !== $optionConfig['default'] || $optionConfig['default_is_null']) {
                $resolver->setDefault($option, $optionConfig['default']);
            }

            if ($optionConfig['required']) {
                $resolver->setRequired($option);
            }
        }

        // Get the transformer list + apply transformer option resolution by context
        $this->configureTransformersOptions($resolver);
        $resolver->setNormalizer('transformers', function (Options $options, $transformerOptions): array {
            if ([] !== $transformerOptions) {
                throw new InvalidArgumentException('Transformers option should not be used at this point');
            }

            $transformerOptions = $this->normalizeTransformerOptions($options, $this->preconfiguredTransformerOptions ?? []);

            return $this->normalizeTransformers($options, $transformerOptions);
        });
    }

    /**
     * @param array<string, mixed> $options
     */
    public function transform(mixed $value, array $options = []): mixed
    {
        return $this->applyTransformers($options['transformers'], $value);
    }

    public function getCode(): string
    {
        if (null === $this->transformerCode) {
            throw new LogicException('The generic transformer is not initialized');
        }

        return $this->transformerCode;
    }

    /**
     * Get the real transformer from contextual options + generic definitions.
     *
     * @param Options<array<string, mixed>> $options
     * @param array<string, mixed>          $transformerOptions
     *
     * @return array<string, mixed>
     */
    public function normalizeTransformerOptions(Options $options, array $transformerOptions): array
    {
        $contextualizedOptionValues = [];
        foreach ($this->contextualOptions ?? [] as $contextualOption => $contextualOptionConfig) {
            // An optional contextual option without default value is replaced by null when not provided
            $contextualizedOptionValues[$contextualOption] = $options->offsetExists($contextualOption)
                ? $options[$contextualOption]
                : null;
        }
        // The former name of a contextual option whose code has a dash, in "{{ former_name }}" (deprecated)
        foreach ($this->deprecatedContextualOptions as $formerName => $contextualOption) {
            $contextualizedOptionValues[$formerName] = $contextualizedOptionValues[$contextualOption] ?? null;
        }

        return $this->contextualOptionResolver->contextualizeOptions($transformerOptions, $contextualizedOptionValues);
    }

    /**
     * Available options for contextual_options.
     */
    public function configureContextualOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('required', true);
        $resolver->setAllowedTypes('required', 'bool');

        $resolver->setDefault('default', null);

        $resolver->setDefault('default_is_null', false);
        $resolver->setAllowedTypes('default_is_null', 'bool');
    }

    /**
     * Before 5.9, the dashes of the transformer codes of a generic transformer were replaced by underscores (unless the
     * code also had an underscore): "array-first" was found as "array_first". Such a code, when it is not registered
     * but its former name is, is replaced by its former name (deprecated).
     *
     * Deprecated since 5.10, will be removed in v6.0.
     *
     * @param array<int|string, mixed> $transformers
     *
     * @return array<int|string, mixed>
     */
    private function replaceFormerTransformerCodes(array $transformers): array
    {
        if (array_is_list($transformers)) {
            return $transformers;
        }
        $replaced = [];
        foreach ($transformers as $key => $transformerOptions) {
            $key = $this->replaceFormerTransformerCode($key, $transformers);
            $replaced[$key] = $transformerOptions;
        }

        return $replaced;
    }

    /**
     * @param array<int|string, mixed> $transformers
     */
    private function replaceFormerTransformerCode(int|string $key, array $transformers): int|string
    {
        if (!\is_string($key) || !str_contains($key, '-') || str_contains($key, '_')) {
            return $key;
        }
        $formerKey = str_replace('-', '_', $key);
        // Both were written: none was renamed
        if (\array_key_exists($formerKey, $transformers)) {
            return $key;
        }
        // The code, without the "#" suffix of the key
        $code = explode('#', $key, 2)[0];
        $formerCode = str_replace('-', '_', $code);
        $registry = $this->transformerRegistry;
        if (!$registry instanceof TransformerRegistry || $registry->hasTransformer($code) || !$registry->hasTransformer($formerCode)) {
            return $key;
        }
        trigger_deprecation(
            'cleverage/process-bundle',
            '5.10',
            'The transformer "%s" of the generic transformer "%s" is not registered, its former name "%s", with underscores instead of dashes, is used: write "%s". The dashes of the transformer codes of a generic transformer were replaced by underscores before 5.9, the former name will not be used in v6.0.',
            $code,
            $this->transformerCode,
            $formerCode,
            $formerCode,
        );

        return $formerKey;
    }

    /**
     * Before 5.9, a contextual option whose code has a dash was named with underscores, also in "{{ former_name }}".
     *
     * Deprecated since 5.10, will be removed in v6.0.
     *
     * @param array<int|string, mixed> $transformers
     */
    private function checkFormerContextualOptionPlaceholders(array $transformers): void
    {
        if ([] === $this->deprecatedContextualOptions) {
            return;
        }
        $formerNames = array_map(static fn (string $formerName): string => preg_quote($formerName, '/'), array_keys($this->deprecatedContextualOptions));
        $pattern = \sprintf('/{{[ ]*(%s)[ ]*}}/', implode('|', $formerNames));
        $used = [];
        foreach (self::getStrings($transformers) as $string) {
            if (preg_match_all($pattern, $string, $matches)) {
                $used = [...$used, ...$matches[1]];
            }
        }
        foreach (array_unique($used) as $formerName) {
            trigger_deprecation(
                'cleverage/process-bundle',
                '5.10',
                'Using the contextual option "%s" of the generic transformer "%s" as "{{ %s }}", with underscores instead of dashes, is deprecated: write "{{ %s }}". The dashes of the contextual option codes were replaced by underscores before 5.9, the former name will not be replaced in v6.0.',
                $this->deprecatedContextualOptions[$formerName],
                $this->transformerCode,
                $formerName,
                $this->deprecatedContextualOptions[$formerName],
            );
        }
    }

    /**
     * A contextual option whose code has a dash can also be given by its former name, with underscores (deprecated).
     *
     * Deprecated since 5.10, will be removed in v6.0.
     *
     * @param array{required: bool, default: mixed, default_is_null: bool} $optionConfig
     */
    private function configureFormerContextualOption(OptionsResolver $resolver, string $option, string $formerName, array $optionConfig): void
    {
        $resolver->setDefined($formerName);
        $transformerCode = $this->transformerCode;
        // Given by its code, by its former name, or the default value
        $resolver->setDefault($option, static function (Options $options) use ($option, $formerName, $optionConfig, $transformerCode): mixed {
            if ($options->offsetExists($formerName)) {
                trigger_deprecation(
                    'cleverage/process-bundle',
                    '5.10',
                    'Using the contextual option "%s" of the generic transformer "%s" by the name "%s", with underscores instead of dashes, is deprecated: use "%s". The dashes of the contextual option codes were replaced by underscores before 5.9, the former name will not be accepted in v6.0.',
                    $option,
                    $transformerCode,
                    $formerName,
                    $option,
                );

                return $options[$formerName];
            }
            if (null !== $optionConfig['default'] || $optionConfig['default_is_null']) {
                return $optionConfig['default'];
            }
            if ($optionConfig['required']) {
                throw new MissingOptionsException(\sprintf('The required option "%s" is missing.', $option));
            }

            return null;
        });
    }

    /**
     * The strings of the transformer options, keys and values, where "{{ code }}" is replaced.
     *
     * @param array<int|string, mixed> $values
     *
     * @return list<string>
     */
    private static function getStrings(array $values): array
    {
        $strings = [];
        foreach ($values as $key => $value) {
            $strings[] = (string) $key;
            if (\is_array($value)) {
                $strings = [...$strings, ...self::getStrings($value)];
            } elseif (\is_string($value)) {
                $strings[] = $value;
            }
        }

        return $strings;
    }
}
