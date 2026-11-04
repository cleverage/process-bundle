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

namespace CleverAge\ProcessBundle\Tests\Transformer\String;

use CleverAge\ProcessBundle\Transformer\String\SprintfTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(SprintfTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(SprintfTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(SprintfTransformer::class, 'getCode')]
#[\PHPUnit\Framework\Attributes\CoversMethod(SprintfTransformer::class, 'configureOptions')]
class SprintfTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $sprintfTransformer = new SprintfTransformer();
        $result = $sprintfTransformer->transform(['bar'], ['format' => 'foo %s']);
        $this->assertEquals('foo bar', $result);

        $result = $sprintfTransformer->transform('bar', ['format' => 'foo %s']);
        $this->assertEquals('foo bar', $result);
    }

    public function testCode(): void
    {
        $trimTransformer = new SprintfTransformer();
        $result = $trimTransformer->getCode();
        $this->assertEquals('sprintf', $result);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideValidFormats(): iterable
    {
        yield 'default' => ['%s'];
        yield 'several' => ['%s-%d-%.2f %%'];
        yield 'positional' => ['%3$s %1$s'];
        yield 'no specification' => ['text'];
        // Width and precision given as an argument (PHP 8): they were rejected, tested with string arguments
        yield 'width argument' => ['%*d'];
        yield 'precision argument' => ['%.*f'];
        yield 'positional width argument' => ['%1$*2$d'];
    }

    #[DataProvider('provideValidFormats')]
    public function testValidFormats(string $format): void
    {
        $resolver = new OptionsResolver();
        (new SprintfTransformer())->configureOptions($resolver);

        $this->assertSame($format, $resolver->resolve(['format' => $format])['format']);
    }

    /**
     * An invalid format was only detected with the first value.
     */
    public function testAnInvalidFormatIsRejected(): void
    {
        $resolver = new OptionsResolver();
        (new SprintfTransformer())->configureOptions($resolver);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Invalid "format" option "50%": Missing format specifier at end of string');
        $resolver->resolve(['format' => '50%']);
    }

    /**
     * The transformation of a format with a width given as an argument.
     */
    public function testWidthArgument(): void
    {
        $resolver = new OptionsResolver();
        $transformer = new SprintfTransformer();
        $transformer->configureOptions($resolver);

        $this->assertSame('   42', $transformer->transform([5, 42], $resolver->resolve(['format' => '%*d'])));
    }
}
