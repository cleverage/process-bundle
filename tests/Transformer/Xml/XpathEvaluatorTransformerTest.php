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

namespace CleverAge\ProcessBundle\Tests\Transformer\Xml;

use CleverAge\ProcessBundle\Transformer\Xml\XpathEvaluatorTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[\PHPUnit\Framework\Attributes\CoversClass(XpathEvaluatorTransformer::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(XpathEvaluatorTransformer::class, 'buildXpath')]
#[\PHPUnit\Framework\Attributes\CoversMethod(XpathEvaluatorTransformer::class, 'query')]
#[\PHPUnit\Framework\Attributes\CoversMethod(XpathEvaluatorTransformer::class, 'transform')]
#[\PHPUnit\Framework\Attributes\CoversMethod(XpathEvaluatorTransformer::class, 'getCode')]
#[\PHPUnit\Framework\Attributes\CoversMethod(XpathEvaluatorTransformer::class, 'configureOptions')]
#[\PHPUnit\Framework\Attributes\CoversMethod(XpathEvaluatorTransformer::class, 'configureQueryOptions')]
class XpathEvaluatorTransformerTest extends TestCase
{
    public function testSimpleQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a>ok</a>');

        $transformer = new XpathEvaluatorTransformer();
        $xpath = $transformer->buildXpath($domDocument);

        $this->assertInstanceOf(\DOMXPath::class, $xpath);

        $options = ['query' => '/a/text()', 'single_result' => true, 'ignore_missing' => true, 'unwrap_value' => true];

        $queryResult = $transformer->query($xpath, '/a/text()', $domDocument, $options);
        $this->assertEquals('ok', $queryResult);

        $result = $transformer->transform($domDocument, $options);
        $this->assertEquals('ok', $result);
    }

    public function testAttributeValueQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<node data="ok">ko</node>');

        $transformer = new XpathEvaluatorTransformer();
        $options = ['query' => '/node/@data', 'single_result' => true, 'ignore_missing' => true, 'unwrap_value' => true];

        $result = $transformer->transform($domDocument, $options);
        $this->assertEquals('ok', $result);
    }

    public function testSubQuery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok</c></b></a>');
        $node = $domDocument->getElementsByTagName('b')[0];

        $transformer = new XpathEvaluatorTransformer();
        $options = ['query' => './c/text()', 'single_result' => true, 'ignore_missing' => true, 'unwrap_value' => true];

        $result = $transformer->transform($node, $options);
        $this->assertEquals('ok', $result);
    }

    public function testMultiResults(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok1</c><c>ok2</c><c>ok3</c></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];

        $transformer = new XpathEvaluatorTransformer();
        $options = ['query' => './c/text()', 'single_result' => false, 'ignore_missing' => true, 'unwrap_value' => true];

        $result = $transformer->transform($node, $options);
        $this->assertEquals(['ok1', 'ok2', 'ok3'], $result);
    }

    public function testMultiResultsAsNodeList(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a><b><c>ok1</c><c>ok2</c><c>ok3</c></b></a>');

        $node = $domDocument->getElementsByTagName('b')[0];

        $transformer = new XpathEvaluatorTransformer();
        $options = ['query' => './c/text()', 'single_result' => false, 'ignore_missing' => true, 'unwrap_value' => false];

        $result = $transformer->transform($node, $options);

        self::assertCount(3, $result);
        self::assertEquals('ok1', $result[0]->textContent);
        self::assertEquals('ok2', $result[1]->textContent);
        self::assertEquals('ok3', $result[2]->textContent);
    }

    public function testGetCodeReturnsCorrectCode(): void
    {
        $transformer = new XpathEvaluatorTransformer();

        $code = $transformer->getCode();

        $this->assertEquals('xpath_evaluator', $code);
    }

    public function testInvalidQueryThrows(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a>ok</a>');
        $transformer = new XpathEvaluatorTransformer();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Invalid xpath query '//[': Invalid expression");

        $transformer->query($transformer->buildXpath($domDocument), '//[', $domDocument, ['unwrap_value' => false, 'single_result' => false]);
    }

    public function testNamespaceNodeCannotBeUnwrapped(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<a xmlns:x="urn:x"/>');
        $transformer = new XpathEvaluatorTransformer();

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage("Xpath result cannot be unwrapped for query '/a/namespace::x'");

        $transformer->query(
            $transformer->buildXpath($domDocument),
            '/a/namespace::x',
            $domDocument,
            ['unwrap_value' => true, 'single_result' => true, 'ignore_missing' => false],
        );
    }

    public function testNodeWithoutDocumentThrows(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The node does not belong to a document');

        (new XpathEvaluatorTransformer())->buildXpath(new \DOMElement('a'));
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function provideScalarExpressions(): iterable
    {
        yield 'number' => ['count(//item)', 2.0];
        yield 'string' => ['string(//item[2]/@id)', 'b'];
        yield 'boolean' => ['//item = "2"', true];
        yield 'sum' => ['sum(//item)', 3.0];
    }

    /**
     * An expression returning a number, a string or a boolean gave null: query() returned an empty node list.
     */
    #[DataProvider('provideScalarExpressions')]
    public function testScalarExpression(string $query, mixed $expected): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<items><item id="a">1</item><item id="b">2</item></items>');
        $transformer = new XpathEvaluatorTransformer();

        $this->assertSame($expected, $transformer->transform($domDocument, $this->resolve(['query' => $query])));
    }

    public function testScalarExpressionInASubquery(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<items><item id="a">1</item><item id="b">2</item></items>');
        $transformer = new XpathEvaluatorTransformer();

        $this->assertSame(
            ['count' => 2.0, 'first' => '1'],
            $transformer->transform($domDocument, $this->resolve(['query' => ['count' => 'count(//item)', 'first' => '//item[1]/text()']]))
        );
    }

    /**
     * A document with a default namespace: its elements must be queried with a registered prefix (an unprefixed query
     * gives no result, without error).
     */
    public function testNamespaces(): void
    {
        $domDocument = new \DOMDocument();
        $domDocument->loadXML('<feed xmlns="http://www.w3.org/2005/Atom"><title>News</title></feed>');
        $transformer = new XpathEvaluatorTransformer();

        $this->assertNull($transformer->transform($domDocument, $this->resolve(['query' => '/feed/title/text()'])));
        $this->assertSame('News', $transformer->transform($domDocument, $this->resolve([
            'query' => '/atom:feed/atom:title/text()',
            'namespaces' => ['atom' => 'http://www.w3.org/2005/Atom'],
        ])));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function provideInvalidNamespaces(): iterable
    {
        yield 'list' => [['http://www.w3.org/2005/Atom']];
        yield 'empty prefix' => [['' => 'http://www.w3.org/2005/Atom']];
        yield 'not a string' => [['atom' => 1]];
        yield 'not an array' => ['atom'];
    }

    #[DataProvider('provideInvalidNamespaces')]
    public function testInvalidNamespaces(mixed $namespaces): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->resolve(['query' => '/a', 'namespaces' => $namespaces]);
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function resolve(array $options): array
    {
        $resolver = new OptionsResolver();
        (new XpathEvaluatorTransformer())->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
