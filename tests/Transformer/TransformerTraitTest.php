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
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\CallbackTransformer;
use CleverAge\ProcessBundle\Transformer\TransformerTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversTrait(TransformerTrait::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(TransformerRegistry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(CallbackTransformer::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(MissingTransformerException::class)]
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

    private function createHolder(): object
    {
        $registry = new TransformerRegistry();
        $registry->addTransformer(new CallbackTransformer());

        return new class($registry) {
            use TransformerTrait;

            public function __construct(TransformerRegistry $transformerRegistry)
            {
                $this->transformerRegistry = $transformerRegistry;
            }

            public function cleanCode(string $code): string
            {
                return $this->getCleanedTransfomerCode($code);
            }

            /**
             * @param array<string, mixed> $transformers
             *
             * @return array<string, \Closure>
             */
            public function resolve(array $transformers): array
            {
                $resolver = new OptionsResolver();
                $this->configureTransformersOptions($resolver);

                return $resolver->resolve(['transformers' => $transformers])['transformers'];
            }

            /**
             * @param array<string, \Closure> $transformers
             */
            public function apply(array $transformers, mixed $value): mixed
            {
                return $this->applyTransformers($transformers, $value);
            }
        };
    }
}
