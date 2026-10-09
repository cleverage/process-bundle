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

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Exception\TransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\CallbackTransformer;
use CleverAge\ProcessBundle\Transformer\String\TrimTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversTrait(TransformerTrait::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CallbackTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TrimTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(MissingTransformerException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerException::class)]
class TransformerTraitTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function transformerCodeProvider(): iterable
    {
        yield 'no suffix' => ['callback', 'callback'];
        yield 'numeric suffix' => ['callback#1', 'callback'];
        yield 'named suffix' => ['callback#reverse', 'callback'];
        yield 'suffix containing #' => ['callback#a#b', 'callback'];
        yield 'unknown transformer' => ['unknown#1', 'unknown#1'];
        yield 'empty suffix' => ['callback#', 'callback#'];
        yield 'suffix only' => ['#1', '#1'];
    }

    #[DataProvider('transformerCodeProvider')]
    public function testCleanedTransformerCode(string $code, string $expected): void
    {
        self::assertSame($expected, $this->createHolder()->cleanCode($code));
    }

    public function testSameTransformerCanBeChainedWithSuffixes(): void
    {
        $holder = $this->createHolder();

        $transformers = $holder->resolve([
            'callback#upper' => ['callback' => 'strtoupper'],
            'callback#reverse' => ['callback' => 'strrev'],
            'callback#1' => ['callback' => 'trim'],
        ]);

        self::assertSame('CBA', $holder->apply($transformers, ' abc'));
    }

    public function testEmptySuffixIsAnUnknownTransformer(): void
    {
        $this->expectException(MissingTransformerException::class);

        $this->createHolder()->resolve(['callback#' => ['callback' => 'trim']]);
    }

    public function testTransformersCanBeGivenAsAList(): void
    {
        $holder = $this->createHolder();

        $transformers = $holder->resolve([
            ['callback' => ['callback' => 'trim']],
            ['callback' => ['callback' => 'strtoupper']],
            ['callback#reverse' => ['callback' => 'strrev']],
        ]);

        self::assertSame(['callback#0', 'callback#1', 'callback#reverse#2'], array_keys($transformers));
        self::assertSame('CBA', $holder->apply($transformers, ' abc '));
    }

    public function testListedTransformerCodeWithoutOptions(): void
    {
        $registry = new TransformerRegistry();
        $registry->addTransformer(new CallbackTransformer());
        $registry->addTransformer(new TrimTransformer());
        $holder = new TransformerTraitHolder($registry);

        $transformers = $holder->resolve(['trim', ['trim' => null], ['callback' => ['callback' => 'strrev']]]);

        self::assertSame('cba', $holder->apply($transformers, ' abc '));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function invalidListedTransformerProvider(): iterable
    {
        yield 'integer' => [1, 'found int'];
        yield 'null' => [null, 'found null'];
        yield 'empty string' => ['', 'found string'];
        yield 'empty map' => [[], 'found array'];
        yield 'map with several codes' => [['callback' => null, 'trim' => null], 'found array'];
        yield 'list' => [['callback'], 'found array'];
    }

    #[DataProvider('invalidListedTransformerProvider')]
    public function testInvalidListedTransformerThrows(mixed $definition, string $found): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Transformer at position 1 is invalid : {$found}, expected a transformer code or a single \"code: options\" map");

        $this->createHolder()->resolve([['callback' => ['callback' => 'trim']], $definition]);
    }

    public function testListedTransformerFailureReportsItsPosition(): void
    {
        $holder = $this->createHolder();
        $transformers = $holder->resolve([
            ['callback' => ['callback' => 'intval']],
            ['callback' => ['callback' => 'intdiv', 'right_parameters' => [0]]],
        ]);

        $this->expectException(TransformerException::class);
        $this->expectExceptionMessage("Transformation 'callback#1' have failed: Division by zero");

        $holder->apply($transformers, ' 1 ');
    }

    public function testMissingRegistryThrows(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No transformer registry defined');

        (new TransformerTraitHolder())->cleanCode('callback#1');
    }

    private function createHolder(): TransformerTraitHolder
    {
        $registry = new TransformerRegistry();
        $registry->addTransformer(new CallbackTransformer());

        return new TransformerTraitHolder($registry);
    }
}
