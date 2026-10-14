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

namespace CleverAge\ProcessBundle\Tests\Registry;

use CleverAge\ProcessBundle\Exception\MissingTransformerException;
use CleverAge\ProcessBundle\Registry\TransformerRegistry;
use CleverAge\ProcessBundle\Transformer\CastTransformer;
use CleverAge\ProcessBundle\Transformer\String\TrimTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[CoversClass(TransformerRegistry::class)]
#[UsesClass(MissingTransformerException::class)]
#[UsesClass(TrimTransformer::class)]
#[UsesClass(CastTransformer::class)]
class TransformerRegistryTest extends TestCase
{
    private int $instantiations = 0;

    public function testTransformerIsInstantiatedOnlyWhenUsed(): void
    {
        $registry = $this->createRegistry();

        self::assertTrue($registry->hasTransformer('trim'));
        self::assertFalse($registry->hasTransformer('unknown'));
        self::assertSame(0, $this->instantiations);

        $transformer = $registry->getTransformer('trim');

        self::assertInstanceOf(TrimTransformer::class, $transformer);
        self::assertSame($transformer, $registry->getTransformer('trim'));
        self::assertSame(1, $this->instantiations);
    }

    public function testMissingTransformerThrows(): void
    {
        $this->expectException(MissingTransformerException::class);

        $this->createRegistry()->getTransformer('unknown');
    }

    public function testCodeMustMatchGetCode(): void
    {
        $registry = new TransformerRegistry(new ServiceLocator(['other' => static fn (): TrimTransformer => new TrimTransformer()]));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The transformer "'.TrimTransformer::class.'" is registered with the code "other" but its getCode() method returns "trim": both must be the same.');

        $registry->getTransformer('other');
    }

    public function testServiceMustBeATransformer(): void
    {
        $registry = new TransformerRegistry(new ServiceLocator(['trim' => static fn (): \stdClass => new \stdClass()]));

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The service of the transformer "trim" must implement');

        $registry->getTransformer('trim');
    }

    public function testGetTransformersReturnsEveryTransformer(): void
    {
        $registry = $this->createRegistry();
        $this->collectDeprecations(static fn () => $registry->addTransformer(new CastTransformer()));

        $transformers = $registry->getTransformers();
        ksort($transformers);
        self::assertSame(['cast', 'trim'], array_keys($transformers));
    }

    public function testAddTransformerIsDeprecated(): void
    {
        $registry = new TransformerRegistry();

        $deprecations = $this->collectDeprecations(static fn () => $registry->addTransformer(new CastTransformer()));

        self::assertInstanceOf(CastTransformer::class, $registry->getTransformer('cast'));
        self::assertSame(
            ['Since cleverage/process-bundle 5.4: Registering the transformer "cast" ('.CastTransformer::class.') with "'.TransformerRegistry::class.'::addTransformer()" is deprecated, tag its service with a "code" attribute ({ name: cleverage.transformer, code: cast }) or use the #[AsTransformer] attribute instead, so that it is only instantiated when used. It will be required in v6.0.'],
            $deprecations
        );
    }

    public function testAddTransformerWithACodeOfTheLocatorThrows(): void
    {
        $registry = $this->createRegistry();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Transformer trim is already defined');

        $this->collectDeprecations(static fn () => $registry->addTransformer(new TrimTransformer()));
    }

    private function createRegistry(): TransformerRegistry
    {
        return new TransformerRegistry(new ServiceLocator([
            'trim' => function (): TrimTransformer {
                ++$this->instantiations;

                return new TrimTransformer();
            },
        ]));
    }

    /**
     * @return list<string>
     */
    private function collectDeprecations(callable $callable): array
    {
        $deprecations = [];
        set_error_handler(static function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, \E_USER_DEPRECATED);
        try {
            $callable();
        } finally {
            restore_error_handler();
        }

        return $deprecations;
    }
}
