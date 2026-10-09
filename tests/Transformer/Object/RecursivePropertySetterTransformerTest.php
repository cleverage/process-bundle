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

namespace CleverAge\ProcessBundle\Tests\Transformer\Object;

use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Transformer\Object\RecursivePropertySetterTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;

#[\PHPUnit\Framework\Attributes\CoversClass(RecursivePropertySetterTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerException::class)]
class RecursivePropertySetterTransformerTest extends TestCase
{
    public function testGetCode(): void
    {
        self::assertSame('recursive_property_setter', $this->createTransformer()->getCode());
    }

    public function testPropertiesArePropagatedToArrayItems(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => '[items]',
            'set_properties' => [
                '[parentId]' => '[id]',
                '[parentName]' => '[name]',
            ],
        ]);

        $result = $transformer->transform(
            ['id' => 1, 'name' => 'Parent', 'items' => [['label' => 'A'], ['label' => 'B']]],
            $options
        );

        self::assertSame([
            ['label' => 'A', 'parentId' => 1, 'parentName' => 'Parent'],
            ['label' => 'B', 'parentId' => 1, 'parentName' => 'Parent'],
        ], $result);
    }

    public function testObjectItemsAreModifiedInPlace(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => 'items',
            'set_properties' => ['parentId' => 'id'],
        ]);
        $item = new \stdClass();
        $item->label = 'A';
        $item->parentId = null;
        $input = (object) ['id' => 7, 'items' => [$item]];

        $result = $transformer->transform($input, $options);

        self::assertIsArray($result);
        self::assertSame($item, $result[0]);
        self::assertSame(7, $item->parentId);
    }

    public function testMissingPropertyIsAddedToStdClassItems(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => 'items',
            'set_properties' => ['parentId' => 'id'],
        ]);

        $result = $transformer->transform((object) ['id' => 7, 'items' => [(object) ['label' => 'A']]], $options);

        self::assertEquals([(object) ['label' => 'A', 'parentId' => 7]], $result);
    }

    public function testTraversableCollectionIsSupported(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => '[items]',
            'set_properties' => ['[parentId]' => '[id]'],
        ]);

        $result = $transformer->transform(['id' => 1, 'items' => new \ArrayObject([['label' => 'A']])], $options);

        self::assertInstanceOf(\ArrayObject::class, $result);
        self::assertSame([['label' => 'A', 'parentId' => 1]], $result->getArrayCopy());
    }

    public function testNonWritableItemPropertyThrows(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => '[items]',
            'set_properties' => ['parentId' => '[id]'],
        ]);

        $this->expectException(NoSuchPropertyException::class);

        $transformer->transform(['id' => 1, 'items' => [new \ArrayIterator()]], $options);
    }

    public function testNullInputThrowsByDefault(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => 'items',
            'set_properties' => [],
        ]);

        $this->expectException(\TypeError::class);

        $transformer->transform(null, $options);
    }

    public function testNullInputReturnsNullWithIgnoreNull(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => 'items',
            'set_properties' => [],
            'ignore_null' => true,
        ]);

        self::assertNull($transformer->transform(null, $options));
    }

    public function testNonIterableCollectionThrows(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => '[items]',
            'set_properties' => [],
        ]);

        $this->expectException(TransformerException::class);
        $this->expectExceptionMessage("Transformation '[items]' have failed");

        $transformer->transform(['items' => 'foo'], $options);
    }

    public function testMissingCollectionThrowsByDefault(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => 'items',
            'set_properties' => [],
        ]);

        $this->expectException(NoSuchPropertyException::class);

        $transformer->transform(new \stdClass(), $options);
    }

    public function testMissingCollectionReturnsNullWithIgnoreMissing(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => 'items',
            'set_properties' => [],
            'ignore_missing' => true,
        ]);

        self::assertNull($transformer->transform(new \stdClass(), $options));
    }

    public function testNullSourceValueThrowsByDefault(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => '[items]',
            'set_properties' => ['[parentId]' => '[id]'],
        ]);

        $this->expectException(TransformerException::class);
        $this->expectExceptionMessage("Transformation '[id]' have failed");

        $transformer->transform(['id' => null, 'items' => [[]]], $options);
    }

    public function testNullSourceValueIsSetWithIgnoreNull(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => '[items]',
            'set_properties' => ['[parentId]' => '[id]'],
            'ignore_null' => true,
        ]);

        self::assertSame(
            [['parentId' => null]],
            $transformer->transform(['id' => null, 'items' => [[]]], $options)
        );
    }

    public function testMissingSourceValueThrowsByDefault(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => 'items',
            'set_properties' => ['[parentId]' => 'missing'],
        ]);

        $this->expectException(NoSuchPropertyException::class);

        $transformer->transform((object) ['items' => [[]]], $options);
    }

    public function testMissingSourceValueIsSetAsNullWithIgnoreMissing(): void
    {
        $transformer = $this->createTransformer();
        $options = $this->resolveOptions($transformer, [
            'iterator' => 'items',
            'set_properties' => ['[parentId]' => 'missing'],
            'ignore_missing' => true,
        ]);

        self::assertSame(
            [['label' => 'A', 'parentId' => null]],
            $transformer->transform((object) ['items' => [['label' => 'A']]], $options)
        );
    }

    public function testRequiredOptions(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required options "iterator", "set_properties" are missing.');

        $this->resolveOptions($this->createTransformer(), []);
    }

    private function createTransformer(): RecursivePropertySetterTransformer
    {
        return new RecursivePropertySetterTransformer(PropertyAccess::createPropertyAccessor());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolveOptions(RecursivePropertySetterTransformer $transformer, array $options): array
    {
        $resolver = new OptionsResolver();
        $transformer->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
