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

namespace CleverAge\ProcessBundle\Tests\Transformer;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\CallbackTransformer;
use CleverAge\ProcessBundle\Transformer\MappingTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(MappingTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesTrait(TransformerTrait::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CallbackTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerException::class)]
class MappingTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('mapping', $this->createTransformer()->getCode());
    }

    public function testMappingIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->resolveOptions($this->createTransformer(), []);
    }

    public function testSimpleMappingFromOneArrayToAnother(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'field2' => ['code' => '[field]'],
            ],
        ]);

        self::assertSame(['field2' => 'value'], $transformer->transform(['field' => 'value'], $options));
    }

    public function testSourcePropertyDefaultsToTargetProperty(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                '[label]' => null,
            ],
        ]);

        self::assertSame(['label' => 'foo'], $transformer->transform(['label' => 'foo', 'other' => 1], $options));
    }

    public function testMissingPropertyThrowsWhenNotIgnored(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'field2' => ['code' => 'missing'],
            ],
        ]);

        $this->expectException(NoSuchPropertyException::class);

        $transformer->transform((object) ['field' => 'value'], $options);
    }

    public function testMissingPropertyIsLogged(): void
    {
        $logger = $this->createCollectingLogger();
        $transformer = $this->createTransformer($logger);
        $options = $this->resolveOptions($transformer, [
            'ignore_missing' => true,
            'mapping' => [
                'field2' => ['code' => 'missing'],
            ],
        ]);

        $transformer->transform((object) ['field' => 'value'], $options);

        self::assertCount(1, $logger->records);
        self::assertSame('Mapping exception', $logger->records[0]['message']);
        self::assertSame('missing', $logger->records[0]['context']['srcKey']);
    }

    public function testGlobalIgnoreMissingSkipsMissingProperties(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'ignore_missing' => true,
            'mapping' => [
                'field2' => ['code' => 'missing'],
                'field3' => ['code' => 'field'],
            ],
        ]);

        self::assertSame(['field3' => 'value'], $transformer->transform((object) ['field' => 'value'], $options));
    }

    public function testPropertyIgnoreMissingSkipsOnlyThisProperty(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'field2' => ['code' => 'missing', 'ignore_missing' => true],
                'field3' => ['code' => 'missing'],
            ],
        ]);

        $this->expectException(NoSuchPropertyException::class);

        $transformer->transform((object) ['field' => 'value'], $options);
    }

    public function testMultipleSourcesBuildAnArray(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'slug' => ['code' => ['name' => '[name]', 'id' => '[id]']],
            ],
        ]);

        self::assertSame(
            ['slug' => ['name' => 'foo', 'id' => 12]],
            $transformer->transform(['name' => 'foo', 'id' => 12], $options)
        );
    }

    public function testMultipleSourcesInSequence(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'out' => ['code' => ['[field1]', '[field2]', '[field3]']],
            ],
        ]);

        self::assertSame(
            ['out' => ['a', 'b', 'c']],
            $transformer->transform(['field1' => 'a', 'field2' => 'b', 'field3' => 'c'], $options)
        );
    }

    public function testMultipleSourcesSkipOnlyMissingKeysWhenIgnored(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'out' => ['code' => ['a' => 'field', 'b' => 'missing'], 'ignore_missing' => true],
            ],
        ]);

        self::assertSame(['out' => ['a' => 'value']], $transformer->transform((object) ['field' => 'value'], $options));
    }

    public function testMultipleSourcesThrowOnMissingKeyWhenNotIgnored(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'out' => ['code' => ['a' => 'field', 'b' => 'missing']],
            ],
        ]);

        $this->expectException(NoSuchPropertyException::class);

        $transformer->transform((object) ['field' => 'value'], $options);
    }

    public function testDotSourceReturnsTheWholeInput(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'out' => ['code' => '.'],
            ],
        ]);

        self::assertSame(['out' => ['value' => 'ok']], $transformer->transform(['value' => 'ok'], $options));
    }

    public function testDotSourceInsideAListOfSources(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'out' => ['code' => ['some_field' => '[field]', 'full' => '.']],
            ],
        ]);

        self::assertSame(
            ['out' => ['some_field' => 'ok', 'full' => ['field' => 'ok']]],
            $transformer->transform(['field' => 'ok'], $options)
        );
    }

    public function testDeepTargetPropertyPathBuildsANestedArray(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                '[field1][field2][field3]' => ['code' => '[value]'],
            ],
        ]);

        self::assertSame(
            ['field1' => ['field2' => ['field3' => 'ok']]],
            $transformer->transform(['value' => 'ok'], $options)
        );
    }

    public function testConstantTakesPrecedenceOverSetNullAndCode(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'required' => ['constant' => true, 'set_null' => true, 'code' => 'missing'],
            ],
        ]);

        self::assertSame(['required' => true], $transformer->transform([], $options));
    }

    public function testSetNullTakesPrecedenceOverCode(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'reference' => ['set_null' => true, 'code' => 'missing'],
            ],
        ]);

        self::assertSame(['reference' => null], $transformer->transform(new \stdClass(), $options));
    }

    public function testNumericTargetPropertiesAreCastToString(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                0 => ['constant' => 'a'],
                1 => ['constant' => 'b'],
            ],
        ]);

        self::assertSame(['a', 'b'], $transformer->transform([], $options));
    }

    public function testTransformersAreAppliedToTheSourceValue(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'code' => [
                    'code' => '[Code]',
                    'transformers' => [
                        'callback' => ['callback' => 'strtolower'],
                    ],
                ],
            ],
        ]);

        self::assertSame(['code' => 'abc'], $transformer->transform(['Code' => 'ABC'], $options));
    }

    public function testSameSubTransformerCanBeUsedMultipleTimesWithSuffixes(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'field2' => [
                    'code' => '[field]',
                    'transformers' => [
                        'callback#1' => ['callback' => 'array_filter'],
                        'callback#2' => ['callback' => 'array_reverse'],
                        'callback#3' => ['callback' => 'array_values'],
                    ],
                ],
            ],
        ]);

        self::assertSame(['field2' => [2, 4, 3]], $transformer->transform(['field' => [3, null, 4, 2]], $options));
    }

    public function testFailingTransformerReportsTheTargetProperty(): void
    {
        $logger = $this->createCollectingLogger();
        $transformer = $this->createTransformer($logger);
        $options = $this->resolveOptions($transformer, [
            'mapping' => [
                'target' => [
                    'code' => '[source]',
                    'transformers' => [
                        'callback' => ['callback' => [self::class, 'failingCallback']],
                    ],
                ],
            ],
        ]);

        try {
            $transformer->transform(['source' => 'foo'], $options);
            self::fail('A TransformerException should have been thrown');
        } catch (TransformerException $exception) {
            self::assertSame(
                "For target property 'target', transformation 'callback' have failed: failing callback",
                $exception->getMessage()
            );
        }

        self::assertCount(1, $logger->records);
        self::assertSame('Transformation exception', $logger->records[0]['message']);
        self::assertSame('failing callback', $logger->records[0]['context']['message']);
    }

    public function testInitialValueIsUsedAsDestination(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'initial_value' => ['existing' => 1],
            'mapping' => [
                'field2' => ['code' => '[field]'],
            ],
        ]);

        self::assertSame(
            ['existing' => 1, 'field2' => 'value'],
            $transformer->transform(['field' => 'value'], $options)
        );
    }

    public function testInitialValueCanBeAnObject(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'initial_value' => (object) ['field2' => null],
            'mapping' => [
                'field2' => ['code' => '[field]'],
            ],
        ]);

        $result = $transformer->transform(['field' => 'value'], $options);

        self::assertEquals((object) ['field2' => 'value'], $result);
    }

    public function testKeepInputCopiesAnArrayInput(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'keep_input' => true,
            'mapping' => [
                '[field2]' => ['code' => '[field]'],
            ],
        ]);
        $input = ['field' => 'value'];

        self::assertSame(['field' => 'value', 'field2' => 'value'], $transformer->transform($input, $options));
        self::assertSame(['field' => 'value'], $input);
    }

    public function testKeepInputModifiesAnObjectInPlace(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'keep_input' => true,
            'mapping' => [
                'address.flag' => ['code' => 'address.postCode'],
            ],
        ]);
        $input = (object) ['address' => (object) ['postCode' => '69005', 'flag' => null]];

        $result = $transformer->transform($input, $options);

        self::assertSame($input, $result);
        self::assertSame('69005', $input->address->flag);
    }

    public function testKeepInputCannotBeCombinedWithInitialValue(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'keep_input' => true,
            'initial_value' => ['foo' => 'bar'],
            'mapping' => [],
        ]);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The options "initial_value" and "keep_input" can\'t be both enabled.');

        $transformer->transform([], $options);
    }

    public function testNonWritableObjectPropertyThrows(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'initial_value' => new \ArrayIterator(),
            'mapping' => [
                'field2' => ['constant' => 'value'],
            ],
        ]);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Property 'field2' is not writable");

        $transformer->transform([], $options);
    }

    public function testMergeCallbackIsUsedToWriteValues(): void
    {
        $destination = new \ArrayObject();
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'initial_value' => $destination,
            'merge_callback' => static function (\ArrayObject $result, string $property, mixed $value): void {
                $result[$property] = strtoupper((string) $value);
            },
            'mapping' => [
                'field2' => ['code' => '[field]'],
            ],
        ]);

        $result = $transformer->transform(['field' => 'value'], $options);

        self::assertSame($destination, $result);
        self::assertSame(['field2' => 'VALUE'], $destination->getArrayCopy());
    }

    public function testMergeCallbackCanModifyAnArrayDestinationByReference(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'merge_callback' => static function (array &$result, string $property, mixed $value): void {
                $result['merged_'.$property] = $value;
            },
            'mapping' => [
                'field2' => ['code' => '[field]'],
            ],
        ]);

        self::assertSame(['merged_field2' => 'value'], $transformer->transform(['field' => 'value'], $options));
    }

    public function testInvalidMappingOptionIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolveOptions($this->createTransformer(), [
            'mapping' => [
                'field' => ['set_null' => 'yes'],
            ],
        ]);
    }

    public static function failingCallback(): never
    {
        throw new \RuntimeException('failing callback');
    }

    private function createTransformer(?LoggerInterface $logger = null): MappingTransformer
    {
        $registry = new TransformerRegistry();
        $registry->addTransformer(new CallbackTransformer());

        return new MappingTransformer(
            $registry,
            $logger ?? new NullLogger(),
            PropertyAccess::createPropertyAccessor()
        );
    }

    /**
     * @return AbstractLogger&object{records: list<array{level: mixed, message: string, context: array<string, mixed>}>}
     */
    private function createCollectingLogger(): AbstractLogger
    {
        return new class extends AbstractLogger {
            /** @var list<array{level: mixed, message: string, context: array<string, mixed>}> */
            public array $records = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
            }
        };
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(MappingTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
